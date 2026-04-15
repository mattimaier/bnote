<?php
/**
 * BNote Next Generation - System Information API module.
 */
declare(strict_types=1);

require_once __DIR__ . "/../response.php";
require_once __DIR__ . "/../auth.php";

class SysteminformationModule
{
  public function __construct()
  {
    if (!Auth::check()) {
      Response::error("Authentication required", 403);
    }
    global $system_data;
    if (!$this->hasSystemInformationPermission($system_data)) {
      Response::error("Access denied to System Information", 403);
    }
  }

  public function handle()
  {
    $action = $_GET["action"] ?? ($_POST["action"] ?? "getOverview");
    switch ($action) {
      case "overview":
      case "getOverview":
        return $this->getOverview();
      default:
        Response::error("Unknown action: " . $action, 400);
    }
  }

  /** @return array<string,mixed> */
  private function getOverview(): array
  {
    global $system_data;

    $changelog = $this->loadChangelog();
    $build = $this->resolveBuildInfo($changelog);
    $modulesRaw = $system_data->getModuleArray();

    return [
      "company" => (string) ($system_data->getCompany() ?? ""),
      "bnote_version_legacy" => $this->resolveLegacyBnoteVersion($system_data),
      "lang" => (string) ($system_data->getLang() ?: "de"),
      "country" => $this->countryAlpha3ToAlpha2(
        (string) ($system_data->getDynamicConfigParameter("default_country") ?? ""),
      ),
      "demo_mode" => (bool) $system_data->inDemoMode(),
      "system_url" => $this->systemUrl($system_data),
      "modules_count" => $this->countModules($modulesRaw),
      "wrapped_enabled" => (string) ($system_data->getDynamicConfigParameter("wrapped_module_enabled") ?? "") === "1",
      "nextgen" => $build,
      "changelog" => [
        "releaseId" => (string) ($changelog["releaseId"] ?? ""),
        "generatedAt" => (string) ($changelog["generatedAt"] ?? ""),
        "entryCount" => $this->countEntries($changelog),
      ],
    ];
  }

  /** @return array<string,mixed> */
  private function loadChangelog(): array
  {
    $path = __DIR__ . "/../config/beta-changelog.json";
    if (!is_readable($path)) {
      return [];
    }
    $raw = file_get_contents($path);
    if (!is_string($raw) || $raw === "") {
      return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
  }

  /** @param array<string,mixed> $changelog */
  private function resolveBuildInfo(array $changelog): array
  {
    $build = [];
    if (!empty($changelog["build"]) && is_array($changelog["build"])) {
      $build = $changelog["build"];
    }

    return [
      "version" => trim((string) ($build["version"] ?? getenv("NEXT_PUBLIC_APP_VERSION") ?: "unknown")),
      "buildId" => trim((string) ($build["buildId"] ?? getenv("NEXT_PUBLIC_APP_BUILD_ID") ?: "unknown")),
      "commit" => trim((string) ($build["commit"] ?? getenv("NEXT_PUBLIC_APP_COMMIT") ?: "unknown")),
      "fullCommit" => trim((string) ($build["fullCommit"] ?? "")),
      "buildTime" => trim((string) ($build["buildTime"] ?? getenv("NEXT_PUBLIC_APP_BUILD_TIME") ?: "unknown")),
    ];
  }

  /** @param mixed $modulesRaw */
  private function countModules($modulesRaw): int
  {
    if (!is_array($modulesRaw)) {
      return 0;
    }
    $count = 0;
    foreach ($modulesRaw as $row) {
      if (is_array($row)) {
        $count++;
      }
    }
    return $count;
  }

  /** @param array<string,mixed> $changelog */
  private function countEntries(array $changelog): int
  {
    if (empty($changelog["entries"]) || !is_array($changelog["entries"])) {
      return 0;
    }
    $count = 0;
    foreach ($changelog["entries"] as $row) {
      if (is_array($row)) {
        $count++;
      }
    }
    return $count;
  }

  private function systemUrl($system_data): string
  {
    if (method_exists($system_data, "getSystemURL")) {
      return trim((string) ($system_data->getSystemURL() ?? ""));
    }
    return "";
  }

  private function resolveLegacyBnoteVersion($system_data): string
  {
    $methodCandidates = ["getVersion", "getSystemVersion", "getBnoteVersion", "getBNoteVersion"];
    foreach ($methodCandidates as $method) {
      if (method_exists($system_data, $method)) {
        $value = trim((string) ($system_data->$method() ?? ""));
        if ($value !== "") {
          return $value;
        }
      }
    }

    $fileCandidates = [BNOTE_ROOT . "/VERSION", BNOTE_ROOT . "/version.txt", BNOTE_ROOT . "/config/version.txt"];
    foreach ($fileCandidates as $path) {
      if (!is_readable($path)) {
        continue;
      }
      $raw = trim((string) file_get_contents($path));
      if ($raw !== "") {
        return preg_split("/\R/", $raw)[0] ?? $raw;
      }
    }

    return "unknown";
  }

  private function countryAlpha3ToAlpha2(string $country): ?string
  {
    $raw = trim($country);
    if ($raw === "") {
      return null;
    }
    $upper = strtoupper($raw);
    if (strlen($upper) === 2) {
      return $upper;
    }
    if (strlen($upper) !== 3) {
      return null;
    }

    $path = __DIR__ . "/../../iso3166-alpha3-to-alpha2.json";
    if (!is_readable($path)) {
      return null;
    }
    $json = file_get_contents($path);
    if (!is_string($json) || $json === "") {
      return null;
    }
    $map = json_decode($json, true);
    if (!is_array($map)) {
      return null;
    }
    return isset($map[$upper]) ? (string) $map[$upper] : null;
  }

  private function hasSystemInformationPermission($system_data): bool
  {
    // Legacy "System Information" lives under Admin; Next Gen currently uses
    // Configuration admin rights as the closest equivalent gate.
    $moduleNames = ["Admin", "Konfiguration", "Configuration"];
    foreach ($moduleNames as $name) {
      $moduleId = (int) $system_data->getModuleId($name);
      if ($moduleId > 0 && $system_data->userHasPermission($moduleId)) {
        return true;
      }
    }
    return false;
  }
}
