<?php
/**
 * BNote Next Generation - Configuration API Module
 */
declare(strict_types=1);

require_once __DIR__ . "/../response.php";
require_once __DIR__ . "/../auth.php";
require_once __DIR__ . "/../nextgen_public_feed_url.php";
require_once __DIR__ . "/../nextgen_public_concerts_feed_token.php";

class ConfigurationModule
{
  private const INSTRUMENT_ALIAS_POOLS_PARAM = "nextgen_instrument_alias_pools";
  private const INSTRUMENT_SECTIONS_PARAM = "nextgen_instrument_sections";

  /** @var array<string,array<string,mixed>> */
  private array $parameterMap = [
    "rehearsal_start" => [
      "type" => "time",
      "section" => "calendar",
      "caption" => "Rehearsal start",
      "used_in_nextgen" => false,
    ],
    "rehearsal_duration" => [
      "type" => "integer",
      "section" => "calendar",
      "caption" => "Rehearsal duration",
      "used_in_nextgen" => false,
    ],
    "default_contact_group" => [
      "type" => "reference_group",
      "section" => "defaults",
      "caption" => "Default contact group",
      "used_in_nextgen" => true,
    ],
    "auto_activation" => [
      "type" => "boolean",
      "section" => "defaults",
      "caption" => "Auto activation",
      "used_in_nextgen" => false,
    ],
    "user_registration" => [
      "type" => "boolean",
      "section" => "defaults",
      "caption" => "User registration",
      "used_in_nextgen" => true,
    ],
    "wrapped_module_enabled" => [
      "type" => "boolean",
      "section" => "display",
      "caption" => "Enable wrapped module",
      "used_in_nextgen" => true,
    ],
    "share_nonadmin_viewmode" => [
      "type" => "boolean",
      "section" => "display",
      "caption" => "Share non-admin view mode",
      "used_in_nextgen" => false,
    ],
    "rehearsal_show_length" => [
      "type" => "boolean",
      "section" => "display",
      "caption" => "Rehearsal length visible",
      "used_in_nextgen" => false,
    ],
    "allow_participation_maybe" => [
      "type" => "boolean",
      "section" => "defaults",
      "caption" => "Allow participation maybe",
      "used_in_nextgen" => true,
    ],
    "allow_zip_download" => [
      "type" => "boolean",
      "section" => "system",
      "caption" => "Allow ZIP download",
      "used_in_nextgen" => false,
    ],
    "appointments_show_max" => [
      "type" => "integer",
      "section" => "display",
      "caption" => "Appointments list max",
      "used_in_nextgen" => false,
    ],
    "rehearsal_show_max" => [
      "type" => "integer",
      "section" => "display",
      "caption" => "Rehearsals list max",
      "used_in_nextgen" => true,
    ],
    "discussion_on" => [
      "type" => "boolean",
      "section" => "system",
      "caption" => "Discussion enabled",
      "used_in_nextgen" => true,
    ],
    "updates_show_max" => [
      "type" => "integer",
      "section" => "display",
      "caption" => "Updates list max",
      "used_in_nextgen" => false,
    ],
    "language" => ["type" => "char", "section" => "defaults", "caption" => "Language", "used_in_nextgen" => false],
    "default_country" => [
      "type" => "char",
      "section" => "defaults",
      "caption" => "Default country",
      "used_in_nextgen" => true,
    ],
    "google_api_key" => [
      "type" => "char",
      "section" => "system",
      "caption" => "Google API key",
      "used_in_nextgen" => false,
    ],
    "trigger_key" => [
      "type" => "char",
      "section" => "notifications",
      "caption" => "Trigger key",
      "used_in_nextgen" => false,
    ],
    "trigger_cycle_days" => [
      "type" => "integer",
      "section" => "notifications",
      "caption" => "Trigger cycle (days)",
      "used_in_nextgen" => false,
    ],
    "trigger_repeat_count" => [
      "type" => "integer",
      "section" => "notifications",
      "caption" => "Trigger repeats",
      "used_in_nextgen" => false,
    ],
    "enable_trigger_service" => [
      "type" => "boolean",
      "section" => "notifications",
      "caption" => "Enable trigger service",
      "used_in_nextgen" => false,
    ],
    "default_conductor" => [
      "type" => "reference_conductor",
      "section" => "defaults",
      "caption" => "Default conductor",
      "used_in_nextgen" => true,
    ],
    "currency" => ["type" => "char", "section" => "defaults", "caption" => "Currency", "used_in_nextgen" => false],
    "concert_show_max" => [
      "type" => "integer",
      "section" => "display",
      "caption" => "Concerts list max",
      "used_in_nextgen" => true,
    ],
    "export_rehearsal_notes" => [
      "type" => "boolean",
      "section" => "system",
      "caption" => "Export rehearsal notes",
      "used_in_nextgen" => false,
    ],
    "export_rehearsalsong_notes" => [
      "type" => "boolean",
      "section" => "system",
      "caption" => "Export rehearsal song notes",
      "used_in_nextgen" => false,
    ],
    "enable_failed_login_log" => [
      "type" => "boolean",
      "section" => "system",
      "caption" => "Enable failed login log",
      "used_in_nextgen" => true,
    ],
    "beta_bug_report_enabled" => [
      "type" => "boolean",
      "section" => "feature_flags",
      "caption" => "Enable beta bug reporting",
      "used_in_nextgen" => true,
    ],
    "beta_bug_report_email" => [
      "type" => "char",
      "section" => "feature_flags",
      "caption" => "Beta bug report inbox",
      "used_in_nextgen" => true,
    ],
    "beta_section_coverage_enabled" => [
      "type" => "boolean",
      "section" => "feature_flags",
      "caption" => "Section-based instrument coverage (beta)",
      "used_in_nextgen" => true,
    ],
    "public_gigs_feed_enabled" => [
      "type" => "boolean",
      "section" => "public_concerts_feed",
      "caption" => "Enable public concerts feed sharing",
      "used_in_nextgen" => true,
    ],
  ];

  public function __construct()
  {
    if (!Auth::check()) {
      Response::error("Authentication required", 403);
    }
    global $system_data;
    if (!$this->hasConfigurationPermission($system_data)) {
      Response::error("Access denied to Configuration", 403);
    }
  }

  public function handle()
  {
    $action = $_GET["action"] ?? ($_POST["action"] ?? "getConfig");
    switch ($action) {
      case "canAccess":
        header("Cache-Control: private, max-age=30, stale-while-revalidate=60");
        return ["canAccess" => true];
      case "getConfig":
        header("Cache-Control: private, max-age=15, stale-while-revalidate=30");
        return $this->getConfig();
      case "updateConfig":
        return $this->updateConfig();
      case "regeneratePublicConcertsFeedToken":
        return $this->regeneratePublicConcertsFeedToken();
      case "getInstrumentAdminData":
        return $this->getInstrumentAdminData();
      case "createCategory":
        return $this->createCategory();
      case "updateCategory":
        return $this->updateCategory();
      case "deleteCategory":
        return $this->deleteCategory();
      case "createInstrument":
        return $this->createInstrument();
      case "updateInstrument":
        return $this->updateInstrument();
      case "deleteInstrument":
        return $this->deleteInstrument();
      case "saveInstrumentSections":
        return $this->saveInstrumentSections();
      case "seedInstrumentDefaults":
        return $this->seedInstrumentDefaults();
      case "applyBigBandPresetMerge":
        return $this->applyBigBandPresetMerge();
      default:
        Response::error("Unknown action: " . $action, 400);
    }
  }

  private function getConfig(): array
  {
    global $system_data;
    $selection = $system_data->dbcon->getSelection(
      "SELECT param, value FROM configuration WHERE is_active = 1 ORDER BY param",
      [],
    );
    $rawValues = [];
    if (is_array($selection)) {
      for ($i = 1; $i < count($selection); $i++) {
        $row = $selection[$i];
        $param = (string) ($row["param"] ?? "");
        if ($param === "" || !isset($this->parameterMap[$param])) {
          continue;
        }
        $rawValues[$param] = (string) ($row["value"] ?? "");
      }
    }

    $values = [];
    $parameters = [];
    foreach ($this->parameterMap as $param => $meta) {
      $raw = isset($rawValues[$param]) ? (string) $rawValues[$param] : "";
      $values[$param] = $this->normalizeOutValue($meta["type"], $raw);
      $parameters[] = [
        "param" => $param,
        "type" => $meta["type"],
        "section" => $meta["section"],
        "caption" => $meta["caption"],
        "used_in_nextgen" => !empty($meta["used_in_nextgen"]),
      ];
    }

    $publicFeedDerived = $this->buildPublicConcertsFeedDerived($system_data->dbcon);

    return [
      "parameters" => $parameters,
      "values" => $values,
      "options" => [
        "groups" => $this->getGroupOptions($system_data),
        "conductors" => $this->getConductorOptions($system_data),
      ],
      "derived" => $publicFeedDerived,
    ];
  }

  private function updateConfig(): array
  {
    global $system_data;
    $payload = $this->readPayload();
    $values = isset($payload["values"]) && is_array($payload["values"]) ? $payload["values"] : null;
    if (!is_array($values)) {
      Response::error("values object is required", 400);
    }

    foreach ($values as $param => $value) {
      $key = (string) $param;
      if (!isset($this->parameterMap[$key])) {
        continue;
      }
      $meta = $this->parameterMap[$key];
      if (empty($meta["used_in_nextgen"])) {
        // Keep legacy-only parameters readonly in Next-Gen configuration UI.
        continue;
      }
      $normalized = $this->normalizeInputValue((string) $meta["type"], $value);
      // Upsert so newly introduced parameters (without existing row) persist correctly.
      $system_data->dbcon->execute(
        "INSERT INTO configuration (param, value, is_active)
                 VALUES (?, ?, 1)
                 ON DUPLICATE KEY UPDATE value = VALUES(value), is_active = 1",
        [["s", $key], ["s", $normalized]],
      );
    }

    return $this->getConfig();
  }

  private function regeneratePublicConcertsFeedToken(): array
  {
    global $system_data;
    NextGenPublicConcertsFeedToken::regenerate($system_data->dbcon);
    return $this->getConfig();
  }

  private function hasConfigurationPermission($system_data): bool
  {
    $moduleNames = ["Konfiguration", "Configuration"];
    foreach ($moduleNames as $name) {
      $moduleId = (int) $system_data->getModuleId($name);
      if ($moduleId > 0 && $system_data->userHasPermission($moduleId)) {
        return true;
      }
    }
    return false;
  }

  /**
   * @return array<string,string>
   */
  private function buildPublicConcertsFeedDerived(object $db): array
  {
    $baseUrl = NextGenPublicFeedUrl::publicConcertsFeedUrl();
    $tokenizedUrl = $baseUrl;
    try {
      $tokenInfo = NextGenPublicConcertsFeedToken::getOrCreate($db);
      $tokenizedUrl = NextGenPublicFeedUrl::publicConcertsFeedTokenizedUrl((string) $tokenInfo["plainToken"]);
    } catch (Throwable $e) {
      // Keep configuration usable even if token setup fails temporarily.
    }

    return [
      "publicConcertsFeedUrl" => $baseUrl,
      "publicConcertsFeedTokenizedUrl" => $tokenizedUrl,
    ];
  }

  private function getInstrumentAdminData(): array
  {
    global $system_data;
    $categoriesSel = $system_data->dbcon->getSelection("SELECT id, name FROM category ORDER BY name", []);
    $categories = [];
    if (is_array($categoriesSel)) {
      for ($i = 1; $i < count($categoriesSel); $i++) {
        $row = $categoriesSel[$i];
        $id = (int) ($row["id"] ?? 0);
        $name = trim((string) ($row["name"] ?? ""));
        if ($id > 0 && $name !== "") {
          $categories[] = ["id" => $id, "name" => $name];
        }
      }
    }

    $instrumentsSel = $system_data->dbcon->getSelection(
      "SELECT i.id, i.name, i.category AS category_id, c.name AS category_name, i.rank
             FROM instrument i
             LEFT JOIN category c ON c.id = i.category
             ORDER BY c.name, i.rank, i.name",
      [],
    );
    $instruments = [];
    if (is_array($instrumentsSel)) {
      for ($i = 1; $i < count($instrumentsSel); $i++) {
        $row = $instrumentsSel[$i];
        $id = (int) ($row["id"] ?? 0);
        $name = trim((string) ($row["name"] ?? ""));
        if ($id < 1 || $name === "") {
          continue;
        }
        $instruments[] = [
          "id" => $id,
          "name" => $name,
          "category_id" => (int) ($row["category_id"] ?? 0),
          "category_name" => (string) ($row["category_name"] ?? ""),
          "rank" => (int) ($row["rank"] ?? 0),
        ];
      }
    }

    return [
      "categories" => $categories,
      "instruments" => $instruments,
      "sections" => $this->readJsonConfigParam(self::INSTRUMENT_SECTIONS_PARAM, []),
    ];
  }

  private function createCategory(): array
  {
    global $system_data;
    $payload = $this->readPayload();
    $name = trim((string) ($payload["name"] ?? ""));
    if ($name === "") {
      Response::error("Category name is required", 400);
    }
    $system_data->dbcon->execute("INSERT INTO category (name) VALUES (?)", [["s", $name]]);
    return ["success" => true];
  }

  private function updateCategory(): array
  {
    global $system_data;
    $payload = $this->readPayload();
    $id = (int) ($payload["id"] ?? 0);
    $name = trim((string) ($payload["name"] ?? ""));
    if ($id < 1 || $name === "") {
      Response::error("Valid id and name are required", 400);
    }
    $system_data->dbcon->execute("UPDATE category SET name = ? WHERE id = ?", [["s", $name], ["i", $id]]);
    return ["success" => true];
  }

  private function deleteCategory(): array
  {
    global $system_data;
    $payload = $this->readPayload();
    $id = (int) ($payload["id"] ?? 0);
    if ($id < 1) {
      Response::error("Valid id is required", 400);
    }
    $count = (int) $system_data->dbcon->colValue("SELECT COUNT(*) AS cnt FROM instrument WHERE category = ?", "cnt", [
      ["i", $id],
    ]);
    if ($count > 0) {
      Response::error("Category is in use by instruments", 400);
    }
    $system_data->dbcon->execute("DELETE FROM category WHERE id = ?", [["i", $id]]);
    return ["success" => true];
  }

  private function createInstrument(): array
  {
    global $system_data;
    $payload = $this->readPayload();
    $name = trim((string) ($payload["name"] ?? ""));
    $categoryId = (int) ($payload["category_id"] ?? 0);
    $rank = (int) ($payload["rank"] ?? 0);
    if ($name === "") {
      Response::error("Instrument name is required", 400);
    }
    if ($categoryId > 0) {
      $exists = (int) $system_data->dbcon->colValue("SELECT COUNT(*) AS cnt FROM category WHERE id = ?", "cnt", [
        ["i", $categoryId],
      ]);
      if ($exists < 1) {
        Response::error("Category not found", 400);
      }
    }
    $system_data->dbcon->execute("INSERT INTO instrument (name, category, rank) VALUES (?, ?, ?)", [
      ["s", $name],
      ["i", $categoryId],
      ["i", $rank],
    ]);
    return ["success" => true];
  }

  private function updateInstrument(): array
  {
    global $system_data;
    $payload = $this->readPayload();
    $id = (int) ($payload["id"] ?? 0);
    $name = trim((string) ($payload["name"] ?? ""));
    $categoryId = (int) ($payload["category_id"] ?? 0);
    $rank = (int) ($payload["rank"] ?? 0);
    if ($id < 1 || $name === "") {
      Response::error("Valid id and name are required", 400);
    }
    if ($categoryId > 0) {
      $exists = (int) $system_data->dbcon->colValue("SELECT COUNT(*) AS cnt FROM category WHERE id = ?", "cnt", [
        ["i", $categoryId],
      ]);
      if ($exists < 1) {
        Response::error("Category not found", 400);
      }
    }
    $system_data->dbcon->execute("UPDATE instrument SET name = ?, category = ?, rank = ? WHERE id = ?", [
      ["s", $name],
      ["i", $categoryId],
      ["i", $rank],
      ["i", $id],
    ]);
    return ["success" => true];
  }

  private function deleteInstrument(): array
  {
    global $system_data;
    $payload = $this->readPayload();
    $id = (int) ($payload["id"] ?? 0);
    if ($id < 1) {
      Response::error("Valid id is required", 400);
    }
    $count = (int) $system_data->dbcon->colValue("SELECT COUNT(*) AS cnt FROM contact WHERE instrument = ?", "cnt", [
      ["i", $id],
    ]);
    if ($count > 0) {
      Response::error("Instrument is assigned to contacts", 400);
    }
    $system_data->dbcon->execute("DELETE FROM instrument WHERE id = ?", [["i", $id]]);
    return ["success" => true];
  }

  private function saveInstrumentSections(): array
  {
    $payload = $this->readPayload();
    $sections = isset($payload["sections"]) && is_array($payload["sections"]) ? $payload["sections"] : [];
    $sanitized = [];
    $seenIds = [];
    foreach ($sections as $section) {
      if (!is_array($section)) {
        continue;
      }
      $id = trim((string) ($section["id"] ?? ""));
      if ($id === "" || isset($seenIds[$id])) {
        $id = $this->generatePrivateSectionId($seenIds);
      } else {
        $seenIds[$id] = true;
      }
      $name = trim((string) ($section["name"] ?? ""));
      $instrumentIdsRaw =
        isset($section["instrument_ids"]) && is_array($section["instrument_ids"]) ? $section["instrument_ids"] : [];
      $instrumentIds = [];
      foreach ($instrumentIdsRaw as $raw) {
        $instrumentId = (int) $raw;
        if ($instrumentId > 0) {
          $instrumentIds[] = $instrumentId;
        }
      }
      $rehearsalMinTotal = max(0, (int) ($section["rehearsal_min_total"] ?? 0));
      $concertMinTotal = max(0, (int) ($section["concert_min_total"] ?? 0));
      $targetsRaw =
        isset($section["concert_instrument_targets"]) && is_array($section["concert_instrument_targets"])
          ? $section["concert_instrument_targets"]
          : [];
      $targets = [];
      foreach ($targetsRaw as $target) {
        if (!is_array($target)) {
          continue;
        }
        $instrumentId = (int) ($target["instrument_id"] ?? 0);
        $required = max(0, (int) ($target["required"] ?? 0));
        if ($instrumentId < 1 || $required < 1) {
          continue;
        }
        $targets[] = ["instrument_id" => $instrumentId, "required" => $required];
      }
      if ($id === "" || $name === "") {
        continue;
      }
      $sanitized[] = [
        "id" => $id,
        "name" => $name,
        "instrument_ids" => array_values($instrumentIds),
        "rehearsal_min_total" => $rehearsalMinTotal,
        "concert_min_total" => $concertMinTotal,
        "concert_instrument_targets" => $targets,
      ];
    }
    $this->writeJsonConfigParam(self::INSTRUMENT_SECTIONS_PARAM, $sanitized);
    return ["success" => true, "sections" => $sanitized];
  }

  private function seedInstrumentDefaults(): array
  {
    global $system_data;
    $lang = strtolower(trim((string) ($system_data->getLang() ?? "en")));
    $isDe = str_starts_with($lang, "de");
    $isEs = str_starts_with($lang, "es");
    $isFr = str_starts_with($lang, "fr");

    $upsertCategory = function (string $name): int {
      global $system_data;
      $id = (int) $system_data->dbcon->colValue("SELECT id FROM category WHERE name = ? LIMIT 1", "id", [["s", $name]]);
      if ($id > 0) {
        return $id;
      }
      $system_data->dbcon->execute("INSERT INTO category (name) VALUES (?)", [["s", $name]]);
      return (int) $system_data->dbcon->colValue("SELECT id FROM category WHERE name = ? LIMIT 1", "id", [
        ["s", $name],
      ]);
    };
    $upsertInstrument = function (string $name, int $categoryId, int $rank): int {
      global $system_data;
      $id = (int) $system_data->dbcon->colValue("SELECT id FROM instrument WHERE name = ? LIMIT 1", "id", [
        ["s", $name],
      ]);
      if ($id > 0) {
        $system_data->dbcon->execute("UPDATE instrument SET category = ?, rank = ? WHERE id = ?", [
          ["i", $categoryId],
          ["i", $rank],
          ["i", $id],
        ]);
        return $id;
      }
      $system_data->dbcon->execute("INSERT INTO instrument (name, category, rank) VALUES (?, ?, ?)", [
        ["s", $name],
        ["i", $categoryId],
        ["i", $rank],
      ]);
      return (int) $system_data->dbcon->colValue("SELECT id FROM instrument WHERE name = ? LIMIT 1", "id", [
        ["s", $name],
      ]);
    };

    $labels = [
      "categoryBrass" => $isDe ? "Blech" : ($isEs ? "Metales" : ($isFr ? "Cuivres" : "Brass")),
      "categorySaxes" => $isDe ? "Saxophone" : ($isEs ? "Saxos" : ($isFr ? "Saxophones" : "Saxes")),
      "categoryRhythm" => $isDe ? "Rhythmusgruppe" : ($isEs ? "Sección rítmica" : ($isFr ? "Rythmique" : "Rhythm")),
      "categoryChoir" => $isDe ? "Chor" : ($isEs ? "Coro" : ($isFr ? "Chœur" : "Choir")),
      "trumpet" => $isDe ? "Trompete" : ($isEs ? "Trompeta" : ($isFr ? "Trompette" : "Trumpet")),
      "trombone" => $isDe ? "Posaune" : ($isEs ? "Trombón" : ($isFr ? "Trombone" : "Trombone")),
      "altoSax" => $isDe ? "Altsaxophon" : ($isEs ? "Saxo alto" : ($isFr ? "Saxophone alto" : "Alto Sax")),
      "tenorSax" => $isDe ? "Tenorsaxophon" : ($isEs ? "Saxo tenor" : ($isFr ? "Saxophone ténor" : "Tenor Sax")),
      "bariSax" => $isDe
        ? "Bariton Saxophon"
        : ($isEs
          ? "Saxo barítono"
          : ($isFr
            ? "Saxophone baryton"
            : "Baritone Sax")),
      "piano" => "Piano",
      "guitar" => $isDe ? "Gitarre" : ($isEs ? "Guitarra" : ($isFr ? "Guitare" : "Guitar")),
      "eBass" => "E-Bass",
      "doubleBass" => $isDe ? "Kontrabass" : ($isEs ? "Contrabajo" : ($isFr ? "Contrebasse" : "Double Bass")),
      "bass" => "Bass",
      "drums" => $isDe ? "Schlagzeug" : ($isEs ? "Batería" : ($isFr ? "Batterie" : "Drums")),
      "voice" => $isDe ? "Stimme" : ($isEs ? "Voz" : ($isFr ? "Voix" : "Voice")),
      "pianoAlt" => $isDe ? "Klavier / ePiano" : "Piano",
    ];

    $catBrass = $upsertCategory($labels["categoryBrass"]);
    $catSaxes = $upsertCategory($labels["categorySaxes"]);
    $catRhythm = $upsertCategory($labels["categoryRhythm"]);
    $catChoir = $upsertCategory($labels["categoryChoir"]);

    $upsertInstrument($labels["trumpet"], $catBrass, 10);
    $upsertInstrument($labels["trombone"], $catBrass, 20);
    $upsertInstrument($labels["altoSax"], $catSaxes, 30);
    $upsertInstrument($labels["tenorSax"], $catSaxes, 40);
    $upsertInstrument($labels["bariSax"], $catSaxes, 50);
    $upsertInstrument($labels["piano"], $catRhythm, 60);
    $upsertInstrument($labels["pianoAlt"], $catRhythm, 61);
    $upsertInstrument($labels["guitar"], $catRhythm, 70);
    $upsertInstrument($labels["eBass"], $catRhythm, 80);
    $upsertInstrument($labels["doubleBass"], $catRhythm, 90);
    $upsertInstrument($labels["bass"], $catRhythm, 91);
    $upsertInstrument($labels["drums"], $catRhythm, 100);
    $upsertInstrument($labels["voice"], $catChoir, 110);

    $instrumentByName = $this->loadInstrumentIdByNameMap();
    $presetConfig = $this->loadInstrumentPresetConfig();
    $seedPresetIds =
      isset($presetConfig["default_seed_presets"]) && is_array($presetConfig["default_seed_presets"])
        ? $presetConfig["default_seed_presets"]
        : ["choir", "big_band"];
    $sections = [];
    foreach ($seedPresetIds as $presetIdRaw) {
      $presetId = trim((string) $presetIdRaw);
      if ($presetId === "") {
        continue;
      }
      $preset = $this->findPresetById($presetConfig, $presetId);
      if ($preset === null) {
        continue;
      }
      $sections = array_merge($sections, $this->buildSectionsFromPreset($preset, $instrumentByName, $lang));
    }
    $this->writeJsonConfigParam(self::INSTRUMENT_ALIAS_POOLS_PARAM, []);
    $this->writeJsonConfigParam(self::INSTRUMENT_SECTIONS_PARAM, $sections);
    return ["success" => true];
  }

  private function applyBigBandPresetMerge(): array
  {
    $sections = $this->readJsonConfigParam(self::INSTRUMENT_SECTIONS_PARAM, []);
    if (!is_array($sections)) {
      $sections = [];
    }
    $presetConfig = $this->loadInstrumentPresetConfig();
    $bigBandPreset = $this->findPresetById($presetConfig, "big_band");
    if ($bigBandPreset === null) {
      return ["success" => true, "sections" => $sections];
    }
    global $system_data;
    $lang = strtolower(trim((string) ($system_data->getLang() ?? "en")));
    $instrumentByName = $this->loadInstrumentIdByNameMap();
    $sections = $this->mergeSectionsFromPresetMissingOnly($sections, $bigBandPreset, $instrumentByName, $lang);
    $this->writeJsonConfigParam(self::INSTRUMENT_SECTIONS_PARAM, $sections);
    return ["success" => true, "sections" => $sections];
  }

  private function generatePrivateSectionId(array &$seenIds): string
  {
    do {
      $id = "sec_" . substr(str_replace(".", "", uniqid("", true)), 0, 12);
    } while (isset($seenIds[$id]));
    $seenIds[$id] = true;
    return $id;
  }

  private function loadInstrumentPresetConfig(): array
  {
    $path = __DIR__ . "/../config/instrument_presets.json";
    if (!is_file($path)) {
      return ["presets" => []];
    }
    $raw = file_get_contents($path);
    if (!is_string($raw) || trim($raw) === "") {
      return ["presets" => []];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : ["presets" => []];
  }

  private function findPresetById(array $presetConfig, string $presetId): ?array
  {
    $presets = isset($presetConfig["presets"]) && is_array($presetConfig["presets"]) ? $presetConfig["presets"] : [];
    foreach ($presets as $preset) {
      if (!is_array($preset)) {
        continue;
      }
      if (trim((string) ($preset["id"] ?? "")) === $presetId) {
        return $preset;
      }
    }
    return null;
  }

  private function loadInstrumentIdByNameMap(): array
  {
    global $system_data;
    $sel = $system_data->dbcon->getSelection(
      "SELECT i.id, i.name, c.name AS category_name
             FROM instrument i
             LEFT JOIN category c ON c.id = i.category",
      [],
    );
    $map = [];
    if (!is_array($sel)) {
      return $map;
    }
    for ($i = 1; $i < count($sel); $i++) {
      $id = (int) ($sel[$i]["id"] ?? 0);
      $name = mb_strtolower(trim((string) ($sel[$i]["name"] ?? "")));
      $categoryName = mb_strtolower(trim((string) ($sel[$i]["category_name"] ?? "")));
      if ($id > 0 && $name !== "") {
        if (!isset($map[$name]) || !is_array($map[$name])) {
          $map[$name] = [];
        }
        $map[$name][] = [
          "id" => $id,
          "category" => $categoryName,
        ];
      }
    }
    return $map;
  }

  private function resolveInstrumentIdByCandidates(
    array $instrumentByName,
    array $candidates,
    array $categoryCandidates = [],
  ): int {
    $normalizedCategoryCandidates = array_values(
      array_filter(
        array_map(static fn($name) => mb_strtolower(trim((string) $name)), $categoryCandidates),
        static fn($name) => $name !== "",
      ),
    );

    foreach ($candidates as $candidateRaw) {
      $candidate = mb_strtolower(trim((string) $candidateRaw));
      if ($candidate === "" || !isset($instrumentByName[$candidate]) || !is_array($instrumentByName[$candidate])) {
        continue;
      }
      $matches = $instrumentByName[$candidate];
      if (count($normalizedCategoryCandidates) > 0) {
        foreach ($matches as $match) {
          $category = (string) ($match["category"] ?? "");
          if ($category !== "" && in_array($category, $normalizedCategoryCandidates, true)) {
            return (int) ($match["id"] ?? 0);
          }
        }
      }
      foreach ($matches as $match) {
        $id = (int) ($match["id"] ?? 0);
        if ($id > 0) {
          return $id;
        }
      }
    }
    return 0;
  }

  private function resolveLocalizedPresetName(array $presetSection, string $lang): string
  {
    $fallback = trim((string) ($presetSection["name"] ?? ""));
    $i18n =
      isset($presetSection["name_i18n"]) && is_array($presetSection["name_i18n"]) ? $presetSection["name_i18n"] : [];
    if (!is_array($i18n) || count($i18n) < 1) {
      return $fallback;
    }
    if ($lang !== "" && isset($i18n[$lang])) {
      $value = trim((string) $i18n[$lang]);
      if ($value !== "") {
        return $value;
      }
    }
    $short = substr($lang, 0, 2);
    if ($short !== "" && isset($i18n[$short])) {
      $value = trim((string) $i18n[$short]);
      if ($value !== "") {
        return $value;
      }
    }
    if (isset($i18n["en"])) {
      $value = trim((string) $i18n["en"]);
      if ($value !== "") {
        return $value;
      }
    }
    return $fallback;
  }

  private function buildSectionsFromPreset(array $preset, array $instrumentByName, string $lang): array
  {
    $sections = [];
    $presetSections = isset($preset["sections"]) && is_array($preset["sections"]) ? $preset["sections"] : [];
    foreach ($presetSections as $presetSection) {
      if (!is_array($presetSection)) {
        continue;
      }
      $id = trim((string) ($presetSection["id"] ?? ""));
      $name = $this->resolveLocalizedPresetName($presetSection, $lang);
      if ($id === "" || $name === "") {
        continue;
      }
      $instrumentIds = [];
      $slots =
        isset($presetSection["instrument_slots"]) && is_array($presetSection["instrument_slots"])
          ? $presetSection["instrument_slots"]
          : [];
      foreach ($slots as $slot) {
        if (!is_array($slot)) {
          continue;
        }
        $candidates = isset($slot["candidates"]) && is_array($slot["candidates"]) ? $slot["candidates"] : [];
        $categoryCandidates =
          isset($slot["category_candidates"]) && is_array($slot["category_candidates"])
            ? $slot["category_candidates"]
            : [];
        $count = max(0, (int) ($slot["count"] ?? 0));
        $instrumentId = $this->resolveInstrumentIdByCandidates($instrumentByName, $candidates, $categoryCandidates);
        if ($instrumentId < 1 || $count < 1) {
          continue;
        }
        for ($idx = 0; $idx < $count; $idx++) {
          $instrumentIds[] = $instrumentId;
        }
      }
      $targets = [];
      $targetItems =
        isset($presetSection["concert_targets"]) && is_array($presetSection["concert_targets"])
          ? $presetSection["concert_targets"]
          : [];
      foreach ($targetItems as $target) {
        if (!is_array($target)) {
          continue;
        }
        $candidates = isset($target["candidates"]) && is_array($target["candidates"]) ? $target["candidates"] : [];
        $categoryCandidates =
          isset($target["category_candidates"]) && is_array($target["category_candidates"])
            ? $target["category_candidates"]
            : [];
        $required = max(0, (int) ($target["required"] ?? 0));
        $instrumentId = $this->resolveInstrumentIdByCandidates($instrumentByName, $candidates, $categoryCandidates);
        if ($instrumentId < 1 || $required < 1) {
          continue;
        }
        $targets[] = ["instrument_id" => $instrumentId, "required" => $required];
      }
      $sections[] = [
        "id" => $id,
        "name" => $name,
        "instrument_ids" => $instrumentIds,
        "rehearsal_min_total" => max(0, (int) ($presetSection["rehearsal_min_total"] ?? 0)),
        "concert_min_total" => max(0, (int) ($presetSection["concert_min_total"] ?? 0)),
        "concert_instrument_targets" => $targets,
      ];
    }
    return $sections;
  }

  private function mergeSectionsFromPresetMissingOnly(
    array $existingSections,
    array $preset,
    array $instrumentByName,
    string $lang,
  ): array {
    $presetSections = $this->buildSectionsFromPreset($preset, $instrumentByName, $lang);
    if (count($presetSections) < 1) {
      return $existingSections;
    }
    $indexById = [];
    foreach ($existingSections as $idx => $section) {
      if (!is_array($section)) {
        continue;
      }
      $id = trim((string) ($section["id"] ?? ""));
      if ($id !== "") {
        $indexById[$id] = $idx;
      }
    }
    foreach ($presetSections as $presetSection) {
      $id = (string) ($presetSection["id"] ?? "");
      if ($id === "") {
        continue;
      }
      if (!isset($indexById[$id])) {
        $existingSections[] = $presetSection;
        continue;
      }
      $idx = (int) $indexById[$id];
      $section = is_array($existingSections[$idx] ?? null) ? $existingSections[$idx] : [];
      if (!isset($section["name"]) || trim((string) $section["name"]) === "") {
        $section["name"] = $presetSection["name"];
      }
      if (!isset($section["rehearsal_min_total"]) || (int) $section["rehearsal_min_total"] < 1) {
        $section["rehearsal_min_total"] = (int) ($presetSection["rehearsal_min_total"] ?? 0);
      }
      if (!isset($section["concert_min_total"]) || (int) $section["concert_min_total"] < 1) {
        $section["concert_min_total"] = (int) ($presetSection["concert_min_total"] ?? 0);
      }
      $existingTargetsRaw =
        isset($section["concert_instrument_targets"]) && is_array($section["concert_instrument_targets"])
          ? $section["concert_instrument_targets"]
          : [];
      $targetMap = [];
      foreach ($existingTargetsRaw as $target) {
        if (!is_array($target)) {
          continue;
        }
        $instrumentId = (int) ($target["instrument_id"] ?? 0);
        $required = max(0, (int) ($target["required"] ?? 0));
        if ($instrumentId > 0 && $required > 0) {
          $targetMap[$instrumentId] = $required;
        }
      }
      foreach ($presetSection["concert_instrument_targets"] ?? [] as $target) {
        if (!is_array($target)) {
          continue;
        }
        $instrumentId = (int) ($target["instrument_id"] ?? 0);
        $required = max(0, (int) ($target["required"] ?? 0));
        if ($instrumentId > 0 && $required > 0 && !isset($targetMap[$instrumentId])) {
          $targetMap[$instrumentId] = $required;
        }
      }
      $section["concert_instrument_targets"] = [];
      foreach ($targetMap as $instrumentId => $required) {
        $section["concert_instrument_targets"][] = [
          "instrument_id" => (int) $instrumentId,
          "required" => (int) $required,
        ];
      }
      if (
        !isset($section["instrument_ids"]) ||
        !is_array($section["instrument_ids"]) ||
        count($section["instrument_ids"]) < 1
      ) {
        $section["instrument_ids"] = is_array($presetSection["instrument_ids"] ?? null)
          ? $presetSection["instrument_ids"]
          : [];
      }
      $existingSections[$idx] = $section;
    }
    return $existingSections;
  }

  /**
   * @param mixed $fallback
   * @return mixed
   */
  private function readJsonConfigParam(string $param, $fallback)
  {
    global $system_data;
    $raw = $system_data->dbcon->colValue("SELECT value FROM configuration WHERE param = ?", "value", [["s", $param]]);
    if (!is_string($raw) || trim($raw) === "") {
      return $fallback;
    }
    $decoded = json_decode($raw, true);
    return json_last_error() === JSON_ERROR_NONE ? $decoded : $fallback;
  }

  /**
   * @param mixed $value
   */
  private function writeJsonConfigParam(string $param, $value): void
  {
    global $system_data;
    $json = json_encode($value);
    $system_data->dbcon->execute(
      "INSERT INTO configuration (param, value, is_active)
             VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE value = VALUES(value), is_active = 1",
      [["s", $param], ["s", $json]],
    );
  }

  /** @return array<int,array<string,mixed>> */
  private function getGroupOptions($system_data): array
  {
    $sel = $system_data->dbcon->getSelection("SELECT id, name FROM `group` WHERE is_active = 1 ORDER BY name", []);
    $groups = [];
    if (is_array($sel)) {
      for ($i = 1; $i < count($sel); $i++) {
        $row = $sel[$i];
        $id = (int) ($row["id"] ?? 0);
        $name = trim((string) ($row["name"] ?? ""));
        if ($id > 0 && $name !== "") {
          $groups[] = ["id" => $id, "name" => $name];
        }
      }
    }
    return $groups;
  }

  /** @return array<int,array<string,mixed>> */
  private function getConductorOptions($system_data): array
  {
    $sel = $system_data->dbcon->getSelection(
      "SELECT id, name, surname FROM contact WHERE is_conductor = 1 ORDER BY surname, name",
      [],
    );
    $conductors = [];
    if (is_array($sel)) {
      for ($i = 1; $i < count($sel); $i++) {
        $row = $sel[$i];
        $id = (int) ($row["id"] ?? 0);
        if ($id < 1) {
          continue;
        }
        $label = trim(((string) ($row["name"] ?? "")) . " " . ((string) ($row["surname"] ?? "")));
        if ($label === "") {
          continue;
        }
        $conductors[] = ["id" => $id, "name" => $label];
      }
    }
    array_unshift($conductors, ["id" => 0, "name" => "-"]);
    return $conductors;
  }

  /** @param mixed $value */
  private function normalizeInputValue(string $type, $value): string
  {
    if ($type === "boolean") {
      return $value === true || $value === 1 || $value === "1" || $value === "true" || $value === "on" ? "1" : "0";
    }
    if ($type === "integer" || $type === "reference_group" || $type === "reference_conductor") {
      return (string) intval($value);
    }
    if ($type === "time") {
      $text = trim((string) $value);
      if (!preg_match('/^\d{1,2}:\d{2}$/', $text)) {
        return "19:30";
      }
      [$h, $m] = array_map("intval", explode(":", $text));
      $h = max(0, min(23, $h));
      $m = max(0, min(59, $m));
      return str_pad((string) $h, 2, "0", STR_PAD_LEFT) . ":" . str_pad((string) $m, 2, "0", STR_PAD_LEFT);
    }
    return trim((string) $value);
  }

  /** @return mixed */
  private function normalizeOutValue(string $type, string $raw)
  {
    if ($type === "boolean") {
      return $raw === "1";
    }
    if ($type === "integer" || $type === "reference_group" || $type === "reference_conductor") {
      return intval($raw);
    }
    return $raw;
  }

  /** @return array<string,mixed> */
  private function readPayload(): array
  {
    $rawInput = file_get_contents("php://input");
    $data = json_decode(is_string($rawInput) ? $rawInput : "", true);
    if (is_array($data)) {
      return $data;
    }
    return is_array($_POST) ? $_POST : [];
  }
}
