<?php
/**
 * Reusable inbox aggregation source, extracted from dashboard logic.
 */
declare(strict_types=1);

require_once BNOTE_ROOT . '/src/data/modules/startdata.php';
require_once BNOTE_ROOT . '/src/data/modules/abstimmungdata.php';
require_once BNOTE_ROOT . '/src/data/database.php';

final class ReminderInboxSource {
    private StartData $startData;
    private AbstimmungData $voteData;
    private object $systemData;

    public function __construct(StartData $startData, AbstimmungData $voteData, object $systemData) {
        $this->startData = $startData;
        $this->voteData = $voteData;
        $this->systemData = $systemData;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function getAllInboxItemsForUser(int $userId): array {
        $items = [];

        $allRehearsals = $this->startData->adp()->getFutureRehearsals(true);
        $userRehearsals = $this->getUserRehearsalIds($userId);
        for ($i = 1; $i < count($allRehearsals); $i++) {
            $r = $allRehearsals[$i];
            if (!in_array((int) $r['id'], $userRehearsals, true)) {
                continue;
            }

            $previewItems = [];
            if (isset($r['groups']) && is_array($r['groups'])) {
                $groupPreview = [];
                foreach ($r['groups'] as $group) {
                    $groupPreview[] = (string) ($group['name'] ?? '');
                }
                $previewItems[] = join('|', $groupPreview);
            }
            $previewItems[] = (string) ($r['name'] ?? '');
            if ((int) ($r['conductor'] ?? 0) > 0) {
                $previewItems[] = (string) $this->startData->adp()->getConductorname((int) $r['conductor']);
            }

            $items[] = [
                'otype' => 'R',
                'oid' => (int) $r['id'],
                'title' => Lang::txt('StartData_inboxItems.rehearsalOn') . ' ' . Data::convertDateFromDb((string) ($r['begin'] ?? '')),
                'preview' => join(', ', $previewItems),
                'due' => Data::convertDateFromDb((string) ($r['approve_until'] ?? '')),
                'eventBegin' => $r['begin'] ?? null,
                'replyUntil' => $r['approve_until'] ?? null,
                'participation' => $this->loadParticipation('R', (int) $r['id'], $userId),
                'status' => $r['status'] ?? null,
            ];
        }

        $allConcerts = $this->startData->adp()->getFutureConcerts($userId);
        for ($i = 1; $i < count($allConcerts); $i++) {
            $c = $allConcerts[$i];
            $items[] = [
                'otype' => 'C',
                'oid' => (int) ($c['id'] ?? 0),
                'title' => Lang::txt('StartData_inboxItems.concertOn') . ' ' . Data::convertDateFromDb((string) ($c['begin'] ?? '')),
                'preview' => (string) (($c['title'] ?? '') . ', ' . ($c['location_name'] ?? '')),
                'due' => Data::convertDateFromDb((string) ($c['approve_until'] ?? '')),
                'eventBegin' => $c['begin'] ?? null,
                'replyUntil' => $c['approve_until'] ?? null,
                'participation' => $this->loadParticipation('C', (int) ($c['id'] ?? 0), $userId),
                'status' => $c['status'] ?? null,
            ];
        }

        $votesSel = $this->startData->getVotesForUser($userId);
        for ($i = 1; $i < count($votesSel); $i++) {
            $v = $votesSel[$i];
            $vid = (int) ($v['id'] ?? 0);
            if ($vid < 1) {
                continue;
            }
            $opts = $this->voteData->getOptions($vid);
            $optCount = is_array($opts) ? max(0, count($opts) - 1) : 0;
            if ($optCount < 1) {
                continue;
            }
            $isMultiDate = !empty($v['is_date']) && !empty($v['is_multi']);
            $participation = $isMultiDate
                ? ($this->hasUserVotedForAllOptions($vid, $userId) ? 1 : -1)
                : ($this->hasUserVoted($vid, $userId) ? 1 : -1);
            $optionsList = [];
            if (is_array($opts)) {
                for ($j = 1; $j < count($opts); $j++) {
                    $row = $opts[$j];
                    $optionsList[] = [
                        'id' => (int) ($row['id'] ?? 0),
                        'name' => (string) ($row['name'] ?? ''),
                        'odate' => $row['odate'] ?? null,
                    ];
                }
            }
            $items[] = [
                'otype' => 'V',
                'oid' => $vid,
                'title' => (string) ($v['name'] ?? ''),
                'preview' => (string) ($v['name'] ?? ''),
                'due' => Data::convertDateFromDb((string) ($v['end'] ?? '')),
                'eventBegin' => $v['end'] ?? null,
                'replyUntil' => $v['end'] ?? null,
                'participation' => $participation,
                'status' => null,
                'vote_options' => $optionsList,
                'vote_user_choices' => $this->getUserChoicesForVote($vid, $userId),
                'vote_is_date' => !empty($v['is_date']),
                'vote_is_multi' => !empty($v['is_multi']),
            ];
        }

        $tasks = $this->startData->adp()->getUserTasks($userId);
        for ($i = 1; $i < count($tasks); $i++) {
            $t = $tasks[$i];
            $dueAt = $t['due_at'] ?? null;
            $eventBegin = $dueAt && $dueAt !== '-' ? $dueAt : ($t['created_at'] ?? null);
            $replyUntil = $dueAt && $dueAt !== '-' ? $dueAt : null;
            $items[] = [
                'otype' => 'T',
                'oid' => (int) ($t['id'] ?? 0),
                'title' => (string) ($t['title'] ?? ''),
                'preview' => isset($t['description']) ? substr((string) $t['description'], 0, 50) : '',
                'due' => $dueAt ? Data::convertDateFromDb((string) $dueAt) : null,
                'eventBegin' => $eventBegin,
                'replyUntil' => $replyUntil,
                'assignee' => trim((string) ($t['assignee'] ?? '')),
                'assigneeFullName' => trim((string) ($t['assignee'] ?? '')),
                'is_complete' => 0,
            ];
        }

        return $items;
    }

    /**
     * @param list<array<string,mixed>> $inboxItems
     * @return list<array<string,mixed>>
     */
    public function formatInboxItems(array $inboxItems): array {
        $formatted = [];
        foreach ($inboxItems as $item) {
            $dueDateRaw = $item['replyUntil'] ?? null;
            $dueDateIso = (is_string($dueDateRaw) && trim($dueDateRaw) !== '' && strlen(trim($dueDateRaw)) >= 10)
                ? trim($dueDateRaw)
                : null;
            $eventName = (string) ($item['title'] ?? '');
            $locationName = null;
            $location = null;
            $otype = $item['otype'] ?? null;
            $oid = (int) ($item['oid'] ?? 0);
            $status = null;

            if ($otype === 'R' && $oid > 0) {
                $txt = (string) Lang::txt('StartData_inboxItems.rehearsalOn');
                $eventName = (string) preg_replace('/\s+(on|am|le)$/i', '', $txt);
                if ($eventName === '' || $eventName === 'StartData_inboxItems.rehearsalOn') {
                    $eventName = 'Rehearsal';
                }
                $rehearsal = $this->startData->getRehearsal($oid);
                if (is_array($rehearsal) && isset($rehearsal['name'])) {
                    $status = $rehearsal['status'] ?? null;
                    if ($this->isSuppressedEventStatus($status)) {
                        continue;
                    }
                    $locationName = (string) ($rehearsal['name'] ?? '');
                    $location = [
                        'name' => $locationName,
                        'street' => $rehearsal['street'] ?? null,
                        'city' => $rehearsal['city'] ?? null,
                        'zip' => $rehearsal['zip'] ?? null,
                    ];
                }
            } elseif ($otype === 'C' && $oid > 0) {
                $preview = (string) ($item['preview'] ?? '');
                $parts = explode(', ', $preview);
                if (count($parts) > 0) {
                    $eventName = trim((string) $parts[0]);
                }
                if (count($parts) > 1) {
                    $locationName = trim((string) $parts[1]);
                    $location = ['name' => $locationName];
                }
                $concert = $this->startData->getConcert($oid);
                if (is_array($concert)) {
                    $status = $concert['status'] ?? null;
                    if ($this->isSuppressedEventStatus($status)) {
                        continue;
                    }
                    if ($locationName === null && isset($concert['location_name'])) {
                        $locationName = (string) $concert['location_name'];
                        $location = ['name' => $locationName];
                    }
                }
            } elseif ($otype === 'RS' || $otype === 'AP') {
                $eventName = (string) ($item['title'] ?? '');
                $locationName = isset($item['location']) ? (string) $item['location'] : null;
                $location = $locationName !== null ? ['name' => $locationName] : null;
            } elseif ($otype === 'V' || $otype === 'T') {
                $eventName = (string) ($item['title'] ?? '');
            }

            $row = [
                'otype' => $otype,
                'oid' => $oid,
                'title' => $eventName,
                'preview' => (string) ($item['preview'] ?? ''),
                'dueDate' => $dueDateIso,
                'dueDateFormatted' => $item['due'] ?? null,
                'participation' => $item['participation'] ?? null,
                'eventBegin' => $item['eventBegin'] ?? null,
                'replyUntil' => $item['replyUntil'] ?? null,
                'status' => $status,
                'location' => $locationName,
                'locationData' => $location,
            ];
            if ($otype === 'T') {
                $row['assignee'] = $item['assignee'] ?? null;
                $row['assigneeFullName'] = $item['assigneeFullName'] ?? ($item['assignee'] ?? null);
                $row['is_complete'] = (int) ($item['is_complete'] ?? 0);
            }
            if ($otype === 'V') {
                $row['vote_options'] = $item['vote_options'] ?? [];
                $row['vote_user_choices'] = $item['vote_user_choices'] ?? [];
                $row['vote_is_date'] = !empty($item['vote_is_date']);
                $row['vote_is_multi'] = !empty($item['vote_is_multi']);
            }
            $formatted[] = $row;
        }
        return $formatted;
    }

    private function isSuppressedEventStatus($status): bool {
        if (!is_string($status)) {
            return false;
        }
        $normalized = strtolower(trim($status));
        return in_array($normalized, ['hidden', 'cancelled', 'canceled'], true);
    }

    /**
     * @param array<string,mixed> $cfg
     * @return array{
     *   items:list<array<string,mixed>>,
     *   events_upcoming:list<array<string,mixed>>,
     *   events_pending_response:list<array<string,mixed>>,
     *   votes:list<array<string,mixed>>,
     *   tasks:list<array<string,mixed>>,
     *   open_count:int,
     *   future_event_count:int
     * }
     */
    public function buildDigestSummaryForUser(int $userId, array $cfg): array {
        $all = $this->formatInboxItems($this->getAllInboxItemsForUser($userId));
        $eventsUpcoming = [];
        $eventsPendingResponse = [];
        $votes = [];
        $tasks = [];
        $openCount = 0;
        $futureEventCount = 0;
        $nowTs = time();
        $upcomingDays = 7;
        $pendingWindowDays = max(1, (int) ($cfg['event_window_days'] ?? 90));
        $upcomingHorizonTs = $nowTs + ($upcomingDays * 86400);
        $pendingHorizonTs = $nowTs + ($pendingWindowDays * 86400);
        $maxEvents = max(1, (int) ($cfg['max_events'] ?? 8));
        $maxVotes = max(1, (int) ($cfg['max_votes'] ?? 5));
        $maxTasks = max(1, (int) ($cfg['max_tasks'] ?? 5));
        $includeVotes = !empty($cfg['include_votes']);
        $includeTasks = !empty($cfg['include_tasks']);

        foreach ($all as $item) {
            $otype = (string) ($item['otype'] ?? '');
            if ($otype === 'R' || $otype === 'C') {
                $eventTs = isset($item['eventBegin']) && is_string($item['eventBegin']) ? strtotime($item['eventBegin']) : false;
                if ($eventTs === false || $eventTs <= 0) {
                    $eventTs = isset($item['replyUntil']) && is_string($item['replyUntil']) ? strtotime($item['replyUntil']) : false;
                }
                if ($eventTs !== false && $eventTs < $nowTs) {
                    continue;
                }
                if ($eventTs !== false) {
                    $futureEventCount++;
                }
                $item['needs_response'] = ((int) ($item['participation'] ?? -1) < 0);
                if (!empty($item['needs_response'])) {
                    $openCount++;
                }
                if ($eventTs !== false && $eventTs <= $upcomingHorizonTs) {
                    $eventsUpcoming[] = $item;
                }
                if (!empty($item['needs_response']) && $eventTs !== false && $eventTs <= $pendingHorizonTs) {
                    $eventsPendingResponse[] = $item;
                }
                continue;
            }
            if ($includeVotes && $otype === 'V' && (int) ($item['participation'] ?? -1) < 0) {
                $endTs = isset($item['eventBegin']) && is_string($item['eventBegin']) ? strtotime($item['eventBegin']) : false;
                if ($endTs !== false && $endTs < $nowTs) {
                    continue;
                }
                $votes[] = $item;
                $openCount++;
                continue;
            }
            if ($includeTasks && $otype === 'T' && (int) ($item['is_complete'] ?? 0) === 0) {
                $tasks[] = $item;
                $openCount++;
            }
        }

        $eventSorter = static function (array $a, array $b): int {
            $aPending = !empty($a['needs_response']) ? 1 : 0;
            $bPending = !empty($b['needs_response']) ? 1 : 0;
            if ($aPending !== $bPending) {
                return $bPending <=> $aPending;
            }
            return strcmp((string) ($a['replyUntil'] ?? ''), (string) ($b['replyUntil'] ?? ''));
        };
        usort($eventsUpcoming, $eventSorter);
        usort($eventsPendingResponse, $eventSorter);
        usort($votes, static function (array $a, array $b): int {
            return strcmp((string) ($a['eventBegin'] ?? ''), (string) ($b['eventBegin'] ?? ''));
        });
        usort($tasks, static function (array $a, array $b): int {
            return strcmp((string) ($a['replyUntil'] ?? ''), (string) ($b['replyUntil'] ?? ''));
        });

        $eventsUpcoming = array_slice($eventsUpcoming, 0, $maxEvents);
        $eventsPendingResponse = array_slice($eventsPendingResponse, 0, $maxEvents);
        $votes = array_slice($votes, 0, $maxVotes);
        $tasks = array_slice($tasks, 0, $maxTasks);
        $items = array_values(array_merge($eventsUpcoming, $eventsPendingResponse, $votes, $tasks));

        return [
            'items' => $items,
            'events_upcoming' => $eventsUpcoming,
            'events_pending_response' => $eventsPendingResponse,
            'votes' => $votes,
            'tasks' => $tasks,
            'open_count' => $openCount,
            'future_event_count' => $futureEventCount,
        ];
    }

    /** @return array<int> */
    private function getUserRehearsalIds(int $userId): array {
        if ($this->systemData->isUserSuperUser($userId)) {
            $allRehearsals = $this->startData->adp()->getFutureRehearsals(true);
            $ids = [];
            for ($i = 1; $i < count($allRehearsals); $i++) {
                $ids[] = (int) $allRehearsals[$i]['id'];
            }
            return $ids;
        }

        $usersPhases = $this->startData->adp()->getUsersPhases($userId);
        $rehearsals = array_merge(
            $this->getRehearsalsForUser($userId),
            $this->getRehearsalsForPhases(is_array($usersPhases) ? $usersPhases : [])
        );
        return array_values(array_unique(array_map('intval', $rehearsals)));
    }

    /** @return array<int> */
    private function getRehearsalsForUser(int $userId): array {
        $query = "SELECT rehearsal
                    FROM rehearsal_contact rc
                        JOIN contact c ON rc.contact = c.id
                        JOIN user u ON u.contact = c.id
                    WHERE u.id = ?";
        $sel = $this->systemData->dbcon->getSelection($query, [['i', $userId]]);
        return Database::flattenSelection($sel, 'rehearsal');
    }

    /**
     * @param array<int,mixed> $phases
     * @return array<int>
     */
    private function getRehearsalsForPhases(array $phases): array {
        if (count($phases) === 0) {
            return [];
        }
        $params = [];
        $whereQ = [];
        foreach ($phases as $p) {
            $whereQ[] = 'rehearsalphase = ?';
            $params[] = ['i', (int) $p];
        }
        $query = 'SELECT rehearsal as id FROM rehearsalphase_rehearsal WHERE ' . join(' OR ', $whereQ);
        $sel = $this->systemData->dbcon->getSelection($query, $params);
        return Database::flattenSelection($sel, 'rehearsal');
    }

    /**
     * @return array<int,string>
     */
    private function getUserChoicesForVote(int $voteId, int $userId): array {
        $query = "SELECT vo.id as option_id, vou.choice
                  FROM vote_option vo
                  LEFT JOIN vote_option_user vou ON vo.id = vou.vote_option AND vou.user = ?
                  WHERE vo.vote = ?";
        $rows = $this->systemData->dbcon->getSelection($query, [['i', $userId], ['i', $voteId]]);
        $choices = [];
        if (is_array($rows)) {
            for ($i = 1; $i < count($rows); $i++) {
                $r = $rows[$i];
                $optId = (int) ($r['option_id'] ?? 0);
                $choice = isset($r['choice']) ? (int) $r['choice'] : 0;
                $choices[$optId] = $choice === 2 ? 'maybe' : ($choice === 1 ? 'yes' : 'no');
            }
        }
        return $choices;
    }

    private function hasUserVoted(int $voteId, int $userId): bool {
        $query = "SELECT COUNT(*) as cnt FROM vote_option vo
                  JOIN vote_option_user vou ON vo.id = vou.vote_option AND vou.user = ?
                  WHERE vo.vote = ?";
        $rows = $this->systemData->dbcon->getSelection($query, [['i', $userId], ['i', $voteId]]);
        return is_array($rows) && isset($rows[1]['cnt']) && (int) $rows[1]['cnt'] > 0;
    }

    private function hasUserVotedForAllOptions(int $voteId, int $userId): bool {
        $optRows = $this->systemData->dbcon->getSelection(
            "SELECT COUNT(*) as cnt FROM vote_option WHERE vote = ?",
            [['i', $voteId]]
        );
        $optCount = (is_array($optRows) && isset($optRows[1]['cnt'])) ? (int) $optRows[1]['cnt'] : 0;
        if ($optCount === 0) {
            return true;
        }
        $votedRows = $this->systemData->dbcon->getSelection(
            "SELECT COUNT(DISTINCT vo.id) as cnt FROM vote_option vo
             JOIN vote_option_user vou ON vo.id = vou.vote_option
             WHERE vo.vote = ? AND vou.user = ?",
            [['i', $voteId], ['i', $userId]]
        );
        $votedCount = (is_array($votedRows) && isset($votedRows[1]['cnt'])) ? (int) $votedRows[1]['cnt'] : 0;
        return $votedCount >= $optCount;
    }

    private function loadParticipation(string $otype, int $eventId, int $userId): int {
        if ($eventId < 1 || $userId < 1) {
            return -1;
        }
        if ($otype === 'R') {
            $row = $this->systemData->dbcon->fetchRow(
                'SELECT participate FROM rehearsal_user WHERE rehearsal = ? AND user = ? LIMIT 1',
                [['i', $eventId], ['i', $userId]]
            );
        } else {
            $row = $this->systemData->dbcon->fetchRow(
                'SELECT participate FROM concert_user WHERE concert = ? AND user = ? LIMIT 1',
                [['i', $eventId], ['i', $userId]]
            );
        }
        if (!is_array($row) || !array_key_exists('participate', $row) || $row['participate'] === null || $row['participate'] === '') {
            return -1;
        }
        return (int) $row['participate'];
    }
}
