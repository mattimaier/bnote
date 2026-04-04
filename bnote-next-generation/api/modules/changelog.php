<?php
/**
 * BNote Next Generation - Changelog API module.
 */
declare(strict_types=1);

require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';

class ChangelogModule {
    public function __construct() {}

    public function handle() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'get';
        switch ($action) {
            case 'get':
            case 'list':
            case 'getChangelog':
                return $this->get();
            default:
                Response::error('Unknown action: ' . $action, 400);
        }
    }

    /** @return array<string,mixed> */
    private function get(): array {
        $raw = $this->loadChangelog();
        $entries = [];
        if (!empty($raw['entries']) && is_array($raw['entries'])) {
            foreach ($raw['entries'] as $row) {
                if (!is_array($row)) continue;
                $bugIdRaw = trim((string) ($row['bugId'] ?? ''));
                $bugId = $bugIdRaw !== '' ? strtoupper($bugIdRaw) : null;
                $title = trim((string) ($row['title'] ?? ''));
                if ($title === '') continue;
                $changeType = strtolower(trim((string) ($row['changeType'] ?? 'changed')));
                if (!in_array($changeType, ['added', 'fixed', 'changed', 'removed'], true)) {
                    $changeType = 'changed';
                }
                $entries[] = [
                    'bugId' => $bugId,
                    'changeType' => $changeType,
                    'title' => $title,
                    'date' => trim((string) ($row['date'] ?? '')),
                ];
            }
        }

        return [
            'releaseId' => (string) ($raw['releaseId'] ?? ''),
            'generatedAt' => (string) ($raw['generatedAt'] ?? ''),
            'build' => $this->resolveBuildInfo($raw),
            'entries' => $entries,
        ];
    }

    /** @return array<string,mixed> */
    private function loadChangelog(): array {
        $path = __DIR__ . '/../config/beta-changelog.json';
        if (!is_readable($path)) return [];
        $raw = file_get_contents($path);
        if (!is_string($raw) || $raw === '') return [];
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<string,mixed> $changelog */
    private function resolveBuildInfo(array $changelog): array {
        $build = [];
        if (!empty($changelog['build']) && is_array($changelog['build'])) {
            $build = $changelog['build'];
        }

        return [
            'version' => trim((string) ($build['version'] ?? getenv('NEXT_PUBLIC_APP_VERSION') ?: 'unknown')),
            'buildId' => trim((string) ($build['buildId'] ?? getenv('NEXT_PUBLIC_APP_BUILD_ID') ?: 'unknown')),
            'commit' => trim((string) ($build['commit'] ?? getenv('NEXT_PUBLIC_APP_COMMIT') ?: 'unknown')),
            'fullCommit' => trim((string) ($build['fullCommit'] ?? '')),
            'buildTime' => trim((string) ($build['buildTime'] ?? getenv('NEXT_PUBLIC_APP_BUILD_TIME') ?: 'unknown')),
        ];
    }
}
