<?php
/**
 * BNote Next Generation - Calendar API Module
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Calendar API module
 * Provides calendar events (rehearsals, concerts, votes, birthdays, reservations, appointments, phases)
 * Uses BNote CalendarData::getEvents() - no modifications to BNote.
 */
require_once BNOTE_ROOT . '/src/data/modules/calendardata.php';
require_once BNOTE_ROOT . '/src/data/modules/appointmentdata.php';
require_once BNOTE_ROOT . '/src/data/modules/startdata.php';
require_once BNOTE_ROOT . '/src/data/modules/mitspielerdata.php';
require_once BNOTE_ROOT . '/src/data/modules/aufgabendata.php';
require_once BNOTE_ROOT . '/src/data/database.php';
require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';

class CalendarModule {
    private $calendarData;
    private $colorMap;

    public function __construct() {
        global $system_data;
        $moduleId = $system_data->getModuleId('Calendar');
        if (!$moduleId || !$system_data->userHasPermission($moduleId)) {
            Response::error('Access denied to Calendar', 403);
        }

        $appointmentData = new AppointmentData();
        $this->calendarData = new CalendarData();
        $this->calendarData->setAppointmentData($appointmentData);

        $this->colorMap = [
            'rehearsal' => '#3399FF',
            'concert' => 'oklch(0.68 0.20 80)',
            'vote' => '#A855F7',
            'contact' => '#14B8A6',
            'reservation' => '#F97316',
            'appointment' => '#8b6914',
            'phase' => 'oklch(0.62 0.18 150)',
            'task' => '#25A65A',
        ];
    }

    public function handle() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'getEvents';

        switch ($action) {
            case 'getEvents':
                return $this->getEvents();
            default:
                Response::error('Unknown action: ' . $action, 400);
        }
    }

    private function getEvents() {
        $from = $_GET['from'] ?? $_POST['from'] ?? null;
        $to = $_GET['to'] ?? $_POST['to'] ?? null;

        if (!$from || !$to) {
            Response::error('from and to (YYYY-MM-DD) required', 400);
        }

        $fromTs = strtotime($from);
        $toTs = strtotime($to);
        if ($fromTs === false || $toTs === false) {
            Response::error('Invalid from/to date format', 400);
        }

        $rawEvents = $this->calendarData->getEvents();
        // Replace rehearsal, concert, vote with range-based fetches (BNote's getEvents only returns future)
        $rawEvents = $this->replaceWithPastEvents($rawEvents, $from, $to);
        $taskEvents = $this->getTaskEvents();
        $rawEvents = array_merge($rawEvents, $taskEvents);
        $events = [];
        $contactIds = [];

        foreach ($rawEvents as $ev) {
            $start = isset($ev['start']) ? str_replace(' ', 'T', $ev['start']) : null;
            if (!$start) continue;

            $bnoteType = $ev['bnoteType'] ?? 'meeting';
            if ($bnoteType === 'contact') {
                $startTrim = trim($start);
                if ($startTrim === '' || strpos($startTrim, '0000-00-00') === 0) continue;
            }

            $startTs = strtotime($start);
            if ($startTs === false) continue;
            if ($startTs > $toTs + 86400) continue;
            $end = isset($ev['end']) ? str_replace(' ', 'T', $ev['end']) : null;
            $endTs = $end ? strtotime($end) : $startTs;
            if ($endTs !== false && $endTs < $fromTs) continue;

            $link = $this->buildLink($bnoteType, $ev['id'] ?? 0, $ev['link'] ?? '');

            $color = $this->colorMap[$bnoteType] ?? 'oklch(0.62 0.18 150)';
            $icon = $this->getIconForType($bnoteType);

            $extendedProps = [
                'bnoteType' => $bnoteType,
                'link' => $link,
                'color' => $color,
                'icon' => $icon,
                'details' => [],
            ];

            if ($bnoteType === 'contact') {
                $contactIds[] = intval($ev['id'] ?? 0);
                $extendedProps['contactId'] = intval($ev['id'] ?? 0);
            }

            $isAllDay = $this->isAllDay($bnoteType, $ev);
            if ($isAllDay && strlen($start) > 10) {
                $start = substr($start, 0, 10);
                $end = substr($end ?: $start, 0, 10);
            }
            $title = $this->stripEventPrefix($ev['title'] ?? '', $bnoteType);
            $events[] = [
                'id' => $this->makeEventId($bnoteType, $ev['id'] ?? 0),
                'title' => $title,
                'start' => $start,
                'end' => $end ?: $start,
                'allDay' => $isAllDay,
                'extendedProps' => $extendedProps,
            ];
        }

        $emailMap = $this->fetchContactEmails($contactIds);
        foreach ($events as &$ev) {
            if (($ev['extendedProps']['bnoteType'] ?? '') === 'contact') {
                $cid = $ev['extendedProps']['contactId'] ?? 0;
                $ev['extendedProps']['avatarEmail'] = $emailMap[$cid] ?? null;
                $ev['extendedProps']['icon'] = 'cake';
                unset($ev['extendedProps']['contactId']);
            }
        }

        return $events;
    }

    /** Strip BNote prefixes (Geb.:, Abst.-Ende:, Res.:, etc.) – show only title/name */
    private function stripEventPrefix($title, $bnoteType) {
        if (!$title) return '';
        $title = trim($title);
        $prefixes = [
            'Geb.: ', 'Geb.:', 'Birthday: ', 'Birthday:', 'Anniversaire: ', 'Anniversaire:',
            'Abst.-Ende: ', 'Abst.-Ende:', 'Abst. End:', 'End of vote: ', 'Fin du vote :', 'Fin du vote:',
            'Res.: ', 'Res.:', 'Réservation: ', 'Réservation:', 'Reservation: ',
            'Termin ', 'Termin:', 'Appointment ', 'Appointment:', 'Rendez-vous ', 'Rendez-vous:',
            'Probe ', 'Rehearsal ', 'Répétition ', 'Auftritt ', 'Concert ',
        ];
        foreach ($prefixes as $p) {
            if (stripos($title, $p) === 0) {
                return trim(substr($title, strlen($p)));
            }
        }
        if (preg_match('/^[^:]+:\s*/u', $title, $m)) {
            return trim(substr($title, strlen($m[0])));
        }
        return $title;
    }

    private function makeEventId($bnoteType, $id) {
        return $bnoteType . '-' . $id;
    }

    private function buildLink($bnoteType, $id, $oldLink) {
        $id = intval($id);
        if (!$id) return '/dashboard';

        switch ($bnoteType) {
            case 'rehearsal':
            case 'concert':
                return '/entity?type=' . $bnoteType . '&id=' . $id;
            case 'contact':
                return '/entity?type=contact&id=' . $id;
            case 'vote':
                return '/votes?id=' . $id;
            case 'reservation':
                return '/entity?type=reservation&id=' . $id;
            case 'appointment':
                return '/entity?type=appointment&id=' . $id;
            case 'phase':
                return '/rehearsals?phase=' . $id;
            case 'task':
                return '/tasks?id=' . $id;
            default:
                return '/dashboard';
        }
    }

    private function getIconForType($bnoteType) {
        $icons = [
            'rehearsal' => 'music',
            'concert' => 'mic-vocal',
            'vote' => 'vote',
            'contact' => 'user-circle',
            'reservation' => 'calendar-check',
            'appointment' => 'calendar',
            'phase' => 'users',
            'task' => 'check-square',
        ];
        return $icons[$bnoteType] ?? 'calendar';
    }

    /**
     * Events with specific begin/end times: allDay false.
     * Date-only events (birthdays, votes, tasks without time): allDay true.
     */
    private function isAllDay($bnoteType, $ev) {
        $start = $ev['start'] ?? '';
        $hasTime = strlen($start) > 10 && strpos($start, 'T') !== false;
        switch ($bnoteType) {
            case 'contact':
                return true;
            case 'vote':
                return true;
            case 'task':
                return !$hasTime;
            case 'rehearsal':
            case 'concert':
            case 'reservation':
            case 'appointment':
            case 'phase':
                return false;
            default:
                return !$hasTime;
        }
    }

    /**
     * Replace rehearsal, concert, vote, appointment events with range-based fetches.
     * BNote CalendarData uses getFutureRehearsals, getFutureConcerts, getVotesForUser which exclude past.
     * BNote uses findAllJoined for appointments (INNER JOIN) which excludes incomplete (null location/contact).
     * We fetch appointments via direct query so incomplete appointments appear.
     */
    private function replaceWithPastEvents(array $rawEvents, $from, $to) {
        $filtered = array_filter($rawEvents, function ($ev) {
            $t = $ev['bnoteType'] ?? '';
            return $t !== 'rehearsal' && $t !== 'concert' && $t !== 'vote' && $t !== 'appointment';
        });
        $rehearsals = $this->getRehearsalsInRange($from, $to);
        $concerts = $this->getConcertsInRange($from, $to);
        $votes = $this->getVotesInRange($from, $to);
        $appointments = $this->getAppointmentsInRange($from, $to);
        return array_merge(array_values($filtered), $rehearsals, $concerts, $votes, $appointments);
    }

    private function getAppointmentsInRange($from, $to) {
        global $system_data;
        $db = $system_data->dbcon;
        $prefix = class_exists('Lang') ? Lang::txt('CalendarData_getEvents.appointment') : 'Appointment';
        $rows = $db->getSelection(
            "SELECT id, begin, end, name FROM appointment WHERE end >= ? AND begin < DATE_ADD(?, INTERVAL 1 DAY) ORDER BY begin ASC",
            [['s', $from], ['s', $to]]
        );
        if (!is_array($rows) || count($rows) < 2) return [];
        $events = [];
        for ($i = 1; $i < count($rows); $i++) {
            $r = $rows[$i];
            $begin = isset($r['begin']) ? str_replace(' ', 'T', $r['begin']) : null;
            if (!$begin) continue;
            $name = trim($r['name'] ?? '');
            $title = $name !== '' ? $prefix . ' ' . $name : $prefix;
            $events[] = [
                'id' => intval($r['id']),
                'start' => $begin,
                'end' => isset($r['end']) ? str_replace(' ', 'T', $r['end']) : $begin,
                'title' => $title,
                'bnoteType' => 'appointment',
                'link' => '/entity?type=appointment&id=' . intval($r['id']),
                'details' => [],
            ];
        }
        return $events;
    }

    private function getRehearsalsInRange($from, $to) {
        global $system_data;
        $db = $system_data->dbcon;
        $uid = Auth::getUserId();
        if (!$uid) return [];

        $adp = $this->calendarData->adp();
        $phases = $adp->getUsersPhases($uid);

        if ($system_data->isUserSuperUser($uid)) {
            $rows = $db->getSelection(
                "SELECT r.id, r.begin, r.end, r.approve_until, r.notes, l.name as location_name " .
                "FROM rehearsal r JOIN location l ON r.location = l.id " .
                "WHERE r.end >= ? AND r.begin < DATE_ADD(?, INTERVAL 1 DAY) ORDER BY r.begin ASC",
                [['s', $from], ['s', $to]]
            );
        } else {
            $userRehs = $db->getSelection(
                "SELECT rehearsal FROM rehearsal_contact rc JOIN contact c ON rc.contact = c.id JOIN user u ON u.contact = c.id WHERE u.id = ?",
                [['i', $uid]]
            );
            $phaseRehs = [];
            if (count($phases) > 0) {
                $phPlaceholders = implode(',', array_fill(0, count($phases), '?'));
                $phParams = array_map(fn($p) => ['i', $p], $phases);
                $phaseSel = $db->getSelection(
                    "SELECT rehearsal as id FROM rehearsalphase_rehearsal WHERE rehearsalphase IN ($phPlaceholders)",
                    $phParams
                );
                $phaseRehs = is_array($phaseSel) ? Database::flattenSelection($phaseSel, 'id') : [];
            }
            $rehearsalIds = array_unique(array_merge(
                is_array($userRehs) ? Database::flattenSelection($userRehs, 'rehearsal') : [],
                $phaseRehs
            ));
            if (count($rehearsalIds) === 0) return [];
            $placeholders = implode(',', array_fill(0, count($rehearsalIds), '?'));
            $params = array_map(fn($id) => ['i', $id], $rehearsalIds);
            $params[] = ['s', $from];
            $params[] = ['s', $to];
            $rows = $db->getSelection(
                "SELECT r.id, r.begin, r.end, r.approve_until, r.notes, l.name as location_name " .
                "FROM rehearsal r JOIN location l ON r.location = l.id " .
                "WHERE r.id IN ($placeholders) AND r.end >= ? AND r.begin < DATE_ADD(?, INTERVAL 1 DAY) " .
                "ORDER BY r.begin ASC",
                $params
            );
        }
        return $this->rehearsalsToEvents($rows);
    }

    private function rehearsalsToEvents($rows) {
        if (!is_array($rows) || count($rows) < 2) return [];
        $prefix = class_exists('Lang') ? Lang::txt('CalendarData_getEvents.rehearsal') : 'Rehearsal';
        $events = [];
        for ($i = 1; $i < count($rows); $i++) {
            $r = $rows[$i];
            $begin = isset($r['begin']) ? str_replace(' ', 'T', $r['begin']) : null;
            if (!$begin) continue;
            $events[] = [
                'id' => intval($r['id']),
                'start' => $begin,
                'end' => isset($r['end']) ? str_replace(' ', 'T', $r['end']) : $begin,
                'title' => $prefix,
                'bnoteType' => 'rehearsal',
                'link' => '?mod=1&mode=view&id=' . intval($r['id']),
                'details' => [],
            ];
        }
        return $events;
    }

    private function getConcertsInRange($from, $to) {
        global $system_data;
        $db = $system_data->dbcon;
        $uid = Auth::getUserId();
        if (!$uid) return [];

        $adp = $this->calendarData->adp();
        if ($system_data->isUserSuperUser($uid)) {
            $rows = $db->getSelection(
                "SELECT c.id, c.title, c.begin, c.end, c.approve_until, c.notes, l.name as location_name, c.outfit " .
                "FROM concert c LEFT JOIN location l ON c.location = l.id " .
                "WHERE c.end >= ? AND c.begin < DATE_ADD(?, INTERVAL 1 DAY) ORDER BY c.begin ASC",
                [['s', $from], ['s', $to]]
            );
        } else {
            $phases = $adp->getUsersPhases($uid);
            $contactId = $adp->getUserContact($uid);
            $phaseWhere = count($phases) > 0
                ? 'rehearsalphase IN (' . implode(',', array_fill(0, count($phases), '?')) . ')'
                : '0 = 1';
            $params = array_merge(
                array_map(fn($p) => ['i', $p], $phases),
                [['i', $contactId], ['s', $from], ['s', $to]]
            );
            $phaseQuery = count($phases) > 0
                ? "SELECT concert FROM rehearsalphase_concert WHERE $phaseWhere"
                : "SELECT concert FROM rehearsalphase_concert WHERE 0 = 1";
            $query = "SELECT DISTINCT c.id, c.title, c.begin, c.end, c.approve_until, c.notes, l.name as location_name, c.outfit " .
                     "FROM concert c LEFT JOIN location l ON c.location = l.id " .
                     "JOIN ($phaseQuery UNION ALL SELECT concert FROM concert_contact WHERE contact = ?) AS concerts ON c.id = concerts.concert " .
                     "WHERE c.end >= ? AND c.begin < DATE_ADD(?, INTERVAL 1 DAY) ORDER BY c.begin ASC";
            $rows = $db->getSelection($query, $params);
        }
        return $this->concertsToEvents($rows);
    }

    private function concertsToEvents($rows) {
        if (!is_array($rows) || count($rows) < 2) return [];
        $prefix = class_exists('Lang') ? Lang::txt('CalendarData_getEvents.concert') : 'Concert';
        $events = [];
        for ($i = 1; $i < count($rows); $i++) {
            $r = $rows[$i];
            $begin = isset($r['begin']) ? str_replace(' ', 'T', $r['begin']) : null;
            if (!$begin) continue;
            $events[] = [
                'id' => intval($r['id']),
                'start' => $begin,
                'end' => isset($r['end']) ? str_replace(' ', 'T', $r['end']) : $begin,
                'title' => ($r['title'] ?? '') !== '' ? $prefix . ' ' . $r['title'] : $prefix,
                'bnoteType' => 'concert',
                'link' => '?mod=2&mode=view&id=' . intval($r['id']),
                'details' => [],
            ];
        }
        return $events;
    }

    private function getVotesInRange($from, $to) {
        global $system_data;
        $db = $system_data->dbcon;
        $uid = Auth::getUserId();
        if (!$uid) return [];
        $rows = $db->getSelection(
            "SELECT v.id, v.name, v.end FROM vote_group vg JOIN vote v ON vg.vote = v.id " .
            "WHERE vg.user = ? AND v.end >= ? AND v.end < DATE_ADD(?, INTERVAL 1 DAY) ORDER BY v.end ASC",
            [['i', $uid], ['s', $from], ['s', $to]]
        );
        return $this->votesToEvents($rows);
    }

    private function votesToEvents($rows) {
        if (!is_array($rows) || count($rows) < 2) return [];
        $prefix = class_exists('Lang') ? Lang::txt('CalendarData_getEvents.end_vote') : 'Vote end:';
        $events = [];
        for ($i = 1; $i < count($rows); $i++) {
            $r = $rows[$i];
            $end = isset($r['end']) ? str_replace(' ', 'T', $r['end']) : null;
            if (!$end) continue;
            $events[] = [
                'id' => intval($r['id']),
                'start' => $end,
                'end' => $end,
                'title' => ($r['name'] ?? '') !== '' ? $prefix . ' ' . $r['name'] : $prefix,
                'bnoteType' => 'vote',
                'link' => '?mod=3&mode=view&id=' . intval($r['id']),
                'details' => [],
            ];
        }
        return $events;
    }

    private function getTaskEvents() {
        $adp = $this->calendarData->adp();
        $uid = Auth::getUserId();
        if (!$uid) return [];

        $tasks = $adp->getUserTasks($uid);
        $events = [];
        if (is_array($tasks)) {
            for ($i = 1; $i < count($tasks); $i++) {
                $t = $tasks[$i];
                $dueAt = $t['due_at'] ?? null;
                if (!$dueAt) continue;

                $events[] = [
                    'id' => intval($t['id'] ?? 0),
                    'title' => $t['title'] ?? '',
                    'start' => str_replace(' ', 'T', $dueAt),
                    'end' => str_replace(' ', 'T', $dueAt),
                    'bnoteType' => 'task',
                    'link' => '/tasks?id=' . intval($t['id'] ?? 0),
                    'details' => [],
                ];
            }
        }
        return $events;
    }

    private function fetchContactEmails(array $contactIds) {
        $contactIds = array_unique(array_filter($contactIds));
        if (empty($contactIds)) return [];

        global $system_data;
        $db = $system_data->dbcon;
        $placeholders = implode(',', array_fill(0, count($contactIds), '?'));
        $params = array_map(fn($id) => ['i', $id], $contactIds);
        $rows = $db->getSelection(
            "SELECT id, email FROM contact WHERE id IN ($placeholders) AND email IS NOT NULL AND email != ''",
            $params
        );
        $map = [];
        if (is_array($rows)) {
            for ($i = 1; $i < count($rows); $i++) {
                $r = $rows[$i];
                $map[intval($r['id'])] = trim($r['email'] ?? '');
            }
        }
        return $map;
    }
}
