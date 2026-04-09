<?php
/**
 * Entity “detail header” context for comment discussion mail (mirrors EventDetail + overview rows).
 */
declare(strict_types=1);

require_once BNOTE_ROOT . '/src/data/modules/probendata.php';
require_once BNOTE_ROOT . '/src/data/modules/konzertedata.php';
require_once BNOTE_ROOT . '/src/data/modules/startdata.php';
require_once __DIR__ . '/MailI18n.php';
require_once __DIR__ . '/MailEntityColors.php';
require_once __DIR__ . '/MailEntityIcons.php';
require_once __DIR__ . '/MailLocaleDateTime.php';

final class CommentDiscussionEntitySummary {
    /**
     * @return array{
     *   icon_bg:string,
     *   icon_inner_html:string,
     *   icon_char:string,
     *   title:string,
     *   badge_label:string,
     *   badge_bg:string,
     *   badge_border:string,
     *   badge_text:string,
     *   meta_line:string,
     *   location_line:string
     * }|null
     */
    public static function load(string $otype, int $oid, $system_data, ?string $locale = null): ?array {
        $otype = strtoupper($otype);
        $resolvedLocale = self::localeFrom($system_data, $locale);
        if ($otype === 'R') {
            return self::loadRehearsal($oid, $system_data, $resolvedLocale);
        }
        if ($otype === 'C') {
            return self::loadConcert($oid, $system_data, $resolvedLocale);
        }
        if ($otype === 'V') {
            return self::loadVote($oid, $system_data, $resolvedLocale);
        }

        return null;
    }

    private static function localeFrom($system_data, ?string $override = null): string {
        $loc = trim((string) ($override ?? ''));
        if ($loc !== '') {
            return strtolower(explode('-', $loc)[0] ?? 'en');
        }
        return method_exists($system_data, 'getLang') ? (string) ($system_data->getLang() ?: 'en') : 'en';
    }

    /** @return array|null */
    private static function loadRehearsal(int $oid, $system_data, string $locale) {
        $pd = new ProbenData();
        $r = $pd->getRehearsal($oid);
        if (!is_array($r) || empty($r['id'])) {
            return null;
        }
        $locName = trim((string) ($r['name'] ?? ''));
        $conductorName = '';
        if (!empty($r['conductor']) && $system_data && isset($system_data->dbcon)) {
            $row = $system_data->dbcon->fetchRow(
                'SELECT name, surname FROM contact WHERE id = ?',
                [['i', (int) $r['conductor']]]
            );
            if (is_array($row)) {
                $conductorName = trim(($row['name'] ?? '') . ' ' . ($row['surname'] ?? ''));
            }
        }
        $title = $locName !== '' ? $locName : ($conductorName !== '' ? $conductorName
            : MailI18n::t('js.event.rehearsal', $locale));
        $begin = (string) ($r['begin'] ?? '');
        $end = (string) ($r['end'] ?? '');
        $meta = MailLocaleDateTime::formatEventMetaLine($begin, $end, $locale);
        $acc = MailEntityColors::commentDiscussionCardAccents('rehearsal');

        return [
            'icon_bg' => $acc['icon_bg'],
            'icon_entity_key' => 'rehearsal',
            'icon_inner_html' => MailEntityIcons::inlineSvgForEntityKey('rehearsal'),
            'icon_char' => "\u{266B}",
            'title' => $title,
            'badge_label' => MailI18n::t('js.event.rehearsal', $locale),
            'badge_bg' => $acc['badge_bg'],
            'badge_border' => $acc['badge_border'],
            'badge_text' => $acc['badge_text'],
            'meta_line' => $meta,
            'location_line' => $locName,
        ];
    }

    /** @return array|null */
    private static function loadConcert(int $oid, $system_data, string $locale) {
        $kd = new KonzerteData();
        $c = $kd->getConcert($oid);
        if (!is_array($c) || empty($c['id'])) {
            return null;
        }
        $title = trim((string) ($c['title'] ?? ''));
        $locName = '';
        if (!empty($c['location']) && isset($system_data->dbcon)) {
            $row = $system_data->dbcon->fetchRow(
                'SELECT name FROM location WHERE id = ?',
                [['i', (int) $c['location']]]
            );
            if (is_array($row)) {
                $locName = trim((string) ($row['name'] ?? ''));
            }
        }
        if ($title === '') {
            $title = $locName !== '' ? $locName : MailI18n::t('js.event.performance', $locale);
        }
        $begin = (string) ($c['begin'] ?? '');
        $end = (string) ($c['end'] ?? '');
        $meta = MailLocaleDateTime::formatEventMetaLine($begin, $end, $locale);
        $acc = MailEntityColors::commentDiscussionCardAccents('concert');

        return [
            'icon_bg' => $acc['icon_bg'],
            'icon_entity_key' => 'concert',
            'icon_inner_html' => MailEntityIcons::inlineSvgForEntityKey('concert'),
            'icon_char' => "\u{266A}",
            'title' => $title,
            'badge_label' => MailI18n::t('js.event.performance', $locale),
            'badge_bg' => $acc['badge_bg'],
            'badge_border' => $acc['badge_border'],
            'badge_text' => $acc['badge_text'],
            'meta_line' => $meta,
            'location_line' => $locName,
        ];
    }

    /** @return array|null */
    private static function loadVote(int $oid, $system_data, string $locale) {
        $sd = new StartData();
        $v = $sd->getVote($oid);
        if (!is_array($v) || empty($v['id'])) {
            return null;
        }
        $name = trim((string) ($v['name'] ?? ''));
        if ($name === '') {
            $name = MailI18n::t('mail.commentDiscussion.fallbackVoteTitle', $locale);
        }
        $end = (string) ($v['end'] ?? '');
        $meta = $end !== '' ? MailLocaleDateTime::formatVoteEndLine($end, $locale) : '';
        $finished = isset($v['is_finished']) && ($v['is_finished'] == '1' || $v['is_finished'] === 1 || $v['is_finished'] === true);
        $badgeLabel = $finished
            ? MailI18n::t('js.votes.finished', $locale)
            : MailI18n::t('js.votes.active', $locale);
        $acc = MailEntityColors::commentDiscussionCardAccents('vote');

        return [
            'icon_bg' => $acc['icon_bg'],
            'icon_entity_key' => 'vote',
            'icon_inner_html' => MailEntityIcons::inlineSvgForEntityKey('vote'),
            'icon_char' => 'V',
            'title' => $name,
            'badge_label' => $badgeLabel,
            'badge_bg' => $acc['badge_bg'],
            'badge_border' => $acc['badge_border'],
            'badge_text' => $acc['badge_text'],
            'meta_line' => $meta,
            'location_line' => '',
        ];
    }
}
