<?php
declare(strict_types=1);

use Sabre\VObject\Component\VCalendar;

if (!ob_get_level()) {
  ob_start();
}
ini_set("display_errors", "0");
$debugIcs = isset($_GET["debug"]) && $_GET["debug"] === "1";

set_exception_handler(static function (Throwable $e) use ($debugIcs): void {
  while (ob_get_level() > 0) {
    ob_end_clean();
  }
  http_response_code(500);
  header("Content-Type: text/plain; charset=utf-8");
  if ($debugIcs) {
    echo "ICS Exception: " . $e->getMessage() . "\n";
    echo $e->getFile() . ":" . $e->getLine() . "\n";
  } else {
    echo "Internal Server Error";
  }
  exit();
});

register_shutdown_function(static function () use ($debugIcs): void {
  $last = error_get_last();
  if (!is_array($last)) {
    return;
  }
  $type = (int) ($last["type"] ?? 0);
  if (!in_array($type, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
    return;
  }
  while (ob_get_level() > 0) {
    ob_end_clean();
  }
  http_response_code(500);
  header("Content-Type: text/plain; charset=utf-8");
  if ($debugIcs) {
    echo "ICS Fatal: " . (string) ($last["message"] ?? "unknown") . "\n";
    echo (string) ($last["file"] ?? "") . ":" . (string) ($last["line"] ?? "") . "\n";
  } else {
    echo "Internal Server Error";
  }
  exit();
});

require_once __DIR__ . "/paths.php";
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$requiredConfigFiles = [BNOTE_ROOT . "/config/config.xml", BNOTE_ROOT . "/config/company.xml"];
foreach ($requiredConfigFiles as $cfgPath) {
  if (!is_file($cfgPath) || !is_readable($cfgPath)) {
    http_response_code(500);
    header("Content-Type: text/plain; charset=utf-8");
    if ($debugIcs) {
      echo "Missing config file: " . $cfgPath;
    } else {
      echo "Internal Server Error";
    }
    exit();
  }
}

$oldErrorReporting = error_reporting(E_ALL & ~E_NOTICE);
$oldDisplayErrors = ini_get("display_errors");
ini_set("display_errors", "0");

$projectRoot = BNOTE_ROOT;
chdir($projectRoot);
if (!isset($GLOBALS["dir_prefix"])) {
  $GLOBALS["dir_prefix"] = "";
}

require_once $projectRoot . "/dirs.php";
require_once $projectRoot . "/src/logic/init.php";
require_once __DIR__ . "/bootstrap.php";
require_once __DIR__ . "/nextgen_calendar_subscription_token.php";
require_once __DIR__ . "/mail/bootstrap.php";
require_once __DIR__ . "/mail/MailEnv.php";
require_once __DIR__ . "/mail/MailLocaleDateTime.php";
require_once BNOTE_ROOT . "/src/data/modules/startdata.php";
require_once BNOTE_ROOT . "/src/data/database.php";

error_reporting($oldErrorReporting);
ini_set("display_errors", (string) $oldDisplayErrors);

if (!class_exists(VCalendar::class)) {
  http_response_code(500);
  header("Content-Type: text/plain; charset=utf-8");
  echo "Missing dependency: sabre/vobject (run composer install in bnote-next-generation/api)";
  exit();
}

$token = isset($_GET["token"]) && is_string($_GET["token"]) ? trim($_GET["token"]) : "";
if ($token === "") {
  http_response_code(400);
  header("Content-Type: text/plain; charset=utf-8");
  echo "Bad Request";
  exit();
}

global $system_data;
$db = $system_data->dbcon;
$userId = NextGenCalendarSubscriptionToken::loadValidUserId($token, $db);
if ($userId < 1) {
  http_response_code(403);
  header("Content-Type: text/plain; charset=utf-8");
  echo "Forbidden";
  exit();
}

$builder = new CalendarIcsFeedBuilder($system_data, $userId);
$ics = $builder->build();

while (ob_get_level() > 0) {
  ob_end_clean();
}

header("Content-Type: text/calendar; charset=utf-8");
if (isset($_GET["download"]) && $_GET["download"] === "1") {
  header('Content-Disposition: attachment; filename="bnote-calendar.ics"');
}
echo $ics;
exit();

final class CalendarIcsFeedBuilder
{
  private object $systemData;
  private object $db;
  private int $userId;
  private object $startData;
  private object $adp;
  private string $lang;
  private string $appBaseUrl;
  private string $uidHost;
  private string $companyName;

  public function __construct(object $systemData, int $userId)
  {
    $this->systemData = $systemData;
    $this->db = $systemData->dbcon;
    $this->userId = $userId;
    $this->startData = new StartData("");
    $this->adp = $this->startData->adp();
    $this->lang = $this->detectLang();
    $this->appBaseUrl = $this->detectAppBaseUrl();
    $this->uidHost = $this->detectUidHost();
    $this->companyName = $this->detectCompanyName();
  }

  public function build(): string
  {
    $calendarName = $this->companyName !== "" ? $this->companyName : "BNote - " . $this->label("calendar");
    $vcalendar = new VCalendar();
    $vcalendar->PRODID = "-//BNote Next Generation//Calendar Feed//EN";
    $vcalendar->VERSION = "2.0";
    $vcalendar->CALSCALE = "GREGORIAN";
    $vcalendar->add("METHOD", "PUBLISH");
    $vcalendar->add("X-WR-CALNAME", $calendarName);

    foreach ($this->getRehearsals() as $reh) {
      $status = strtolower(trim((string) ($reh["status"] ?? "")));
      if ($status === "cancelled" || $status === "hidden") {
        continue;
      }
      $summary = trim($this->label("rehearsal") . " " . $this->companyName);
      $locationAddress = $this->composeAddress(
        (string) ($reh["location_street"] ?? ""),
        (string) ($reh["location_city"] ?? ""),
        (string) ($reh["location_zip"] ?? ""),
      );
      $locationDetails = $this->composeLocationDetails(
        (string) ($reh["location_name"] ?? ""),
        (string) ($reh["location_street"] ?? ""),
        (string) ($reh["location_city"] ?? ""),
        (string) ($reh["location_zip"] ?? ""),
      );
      $description = $this->buildRehearsalDescription($reh, $locationDetails);
      $url = $this->buildEntityUrl("rehearsal", (int) $reh["id"]);

      $vevent = $this->createEvent(
        $vcalendar,
        "rehearsal",
        (int) $reh["id"],
        $summary,
        (string) ($reh["begin"] ?? ""),
        (string) ($reh["end"] ?? ""),
        $locationAddress,
        $description,
        $url,
      );
      $this->addAttendees($vevent, $this->getRehearsalAttendees((int) $reh["id"]));
    }

    foreach ($this->getConcerts() as $concert) {
      $title = trim((string) ($concert["title"] ?? ""));
      $bandName = trim($this->companyName);
      if ($title !== "" && $bandName !== "") {
        $summary = $this->label("concert") . " " . $bandName . " - " . $title;
      } elseif ($title !== "") {
        $summary = $this->label("concert") . " " . $title;
      } elseif ($bandName !== "") {
        $summary = $this->label("concert") . " " . $bandName;
      } else {
        $summary = $this->label("concert");
      }
      $locationAddress = $this->composeAddress(
        (string) ($concert["location_street"] ?? ""),
        (string) ($concert["location_city"] ?? ""),
        (string) ($concert["location_zip"] ?? ""),
      );
      $locationDetails = $this->composeLocationDetails(
        (string) ($concert["location_name"] ?? ""),
        (string) ($concert["location_street"] ?? ""),
        (string) ($concert["location_city"] ?? ""),
        (string) ($concert["location_zip"] ?? ""),
      );
      $description = $this->buildConcertDescription($concert, $locationDetails);
      $url = $this->buildEntityUrl("concert", (int) $concert["id"]);
      $concertBeginRaw = (string) ($concert["begin"] ?? "");
      $concertEndRaw = (string) ($concert["end"] ?? "");
      $meetingTimeRaw = trim((string) ($concert["meetingtime"] ?? ""));

      // Add a dedicated "meeting time" block before the actual concert when possible.
      $meetingStart = $this->parseDbDateTime($meetingTimeRaw);
      $concertStart = $this->parseDbDateTime($concertBeginRaw);
      if ($meetingStart !== null && $concertStart !== null && $meetingStart < $concertStart) {
        $meetingSummary = $this->label("meetingtime") . " " . $summary;
        $meetingEvent = $this->createEvent(
          $vcalendar,
          "concert",
          (int) $concert["id"],
          $meetingSummary,
          $meetingTimeRaw,
          $concertBeginRaw,
          $locationAddress,
          $description,
          $url,
          "meeting",
        );
        $this->addAttendees($meetingEvent, $this->getConcertAttendees((int) $concert["id"]));
      }

      $vevent = $this->createEvent(
        $vcalendar,
        "concert",
        (int) $concert["id"],
        $summary,
        $concertBeginRaw,
        $concertEndRaw,
        $locationAddress,
        $description,
        $url,
        "concert",
      );
      $this->addAttendees($vevent, $this->getConcertAttendees((int) $concert["id"]));
    }

    foreach ($this->getReservations() as $res) {
      $summary = trim((string) ($res["name"] ?? ""));
      if ($summary === "") {
        $summary = $this->label("reservation");
      } else {
        $summary = $this->label("reservation") . " " . $summary;
      }
      $url = $this->buildEntityUrl("reservation", (int) $res["id"]);
      $resAddress = $this->composeAddress(
        (string) ($res["location_street"] ?? ""),
        (string) ($res["location_city"] ?? ""),
        (string) ($res["location_zip"] ?? ""),
      );
      $resLocationDetails = $this->composeLocationDetails(
        (string) ($res["location_name"] ?? ""),
        (string) ($res["location_street"] ?? ""),
        (string) ($res["location_city"] ?? ""),
        (string) ($res["location_zip"] ?? ""),
      );
      $resSections = [];
      $resNotes = $this->normalizeNotesText((string) ($res["notes"] ?? ""));
      if ($resNotes !== "") {
        $resSections[] = $this->label("notes") . ":\n" . $resNotes;
      }
      if ($resLocationDetails !== "") {
        $resSections[] = $this->label("location") . ":\n" . $resLocationDetails;
      }
      $resDesc = $this->joinSections($resSections);
      $this->createEvent(
        $vcalendar,
        "reservation",
        (int) $res["id"],
        $summary,
        (string) ($res["begin"] ?? ""),
        (string) ($res["end"] ?? ""),
        $resAddress,
        $resDesc,
        $url,
      );
    }

    foreach ($this->getAppointments() as $appointment) {
      $summary = trim((string) ($appointment["name"] ?? ""));
      if ($summary === "") {
        $summary = $this->label("appointment");
      }
      $locationAddress = $this->composeAddress(
        (string) ($appointment["street"] ?? ""),
        (string) ($appointment["city"] ?? ""),
        (string) ($appointment["zip"] ?? ""),
      );
      $locationDetails = $this->composeLocationDetails(
        (string) ($appointment["location_name"] ?? ""),
        (string) ($appointment["street"] ?? ""),
        (string) ($appointment["city"] ?? ""),
        (string) ($appointment["zip"] ?? ""),
      );
      $url = $this->buildEntityUrl("appointment", (int) $appointment["id"]);
      $appointmentSections = [];
      $appointmentNotes = $this->normalizeNotesText((string) ($appointment["notes"] ?? ""));
      if ($appointmentNotes !== "") {
        $appointmentSections[] = $this->label("notes") . ":\n" . $appointmentNotes;
      }
      if ($locationDetails !== "") {
        $appointmentSections[] = $this->label("location") . ":\n" . $locationDetails;
      }
      $appointmentDesc = $this->joinSections($appointmentSections);
      $this->createEvent(
        $vcalendar,
        "appointment",
        (int) $appointment["id"],
        $summary,
        (string) ($appointment["begin"] ?? ""),
        (string) ($appointment["end"] ?? ""),
        $locationAddress,
        $appointmentDesc,
        $url,
      );
    }

    foreach ($this->getTours() as $tour) {
      $summary = trim((string) ($tour["name"] ?? ""));
      if ($summary === "") {
        $summary = $this->label("tour");
      }
      $this->createEvent(
        $vcalendar,
        "tour",
        (int) ($tour["id"] ?? 0),
        $summary,
        (string) ($tour["start"] ?? ""),
        (string) ($tour["end"] ?? ""),
        "",
        trim((string) ($tour["notes"] ?? "")),
        $this->appBaseUrl . "/dashboard",
      );
    }

    foreach ($this->getTasks() as $task) {
      $title = trim((string) ($task["title"] ?? ""));
      if ($title === "") {
        continue;
      }
      $begin = trim((string) ($task["due_at"] ?? ""));
      if ($begin === "" || $begin === "0000-00-00 00:00:00") {
        $begin = trim((string) ($task["created_at"] ?? ""));
      }
      if ($begin === "" || $begin === "0000-00-00 00:00:00") {
        continue;
      }
      $summary = $this->label("task") . " " . $title;
      $desc = $this->normalizeNotesText((string) ($task["description"] ?? ""));
      $url = $this->buildEntityUrl("task", (int) ($task["id"] ?? 0));
      $this->createEvent($vcalendar, "task", (int) ($task["id"] ?? 0), $summary, $begin, $begin, "", $desc, $url);
    }

    foreach ($this->getBirthdays() as $birthday) {
      $name = trim((string) ($birthday["name"] ?? ""));
      $surname = trim((string) ($birthday["surname"] ?? ""));
      $full = trim($name . " " . $surname);
      if ($full === "") {
        continue;
      }
      $date = $this->birthdayInCurrentYear((string) ($birthday["birthday"] ?? ""));
      if ($date === null) {
        continue;
      }
      $this->createAllDayEvent(
        $vcalendar,
        "birthday",
        (int) ($birthday["id"] ?? 0),
        $this->label("birthday") . " " . $full,
        $date,
        $this->buildEntityUrl("contact", (int) ($birthday["id"] ?? 0)),
      );
    }

    return $vcalendar->serialize();
  }

  private function createEvent(
    VCalendar $vcalendar,
    string $type,
    int $id,
    string $summary,
    string $beginRaw,
    string $endRaw,
    string $location,
    string $description,
    string $url,
    string $uidSuffix = "",
  ): object {
    $start = $this->parseDbDateTime($beginRaw);
    if ($start === null) {
      $start = new DateTimeImmutable("now", new DateTimeZone("UTC"));
    }
    $end = $this->parseDbDateTime($endRaw);
    if ($end === null || $end <= $start) {
      $end = $start->add(new DateInterval("PT1H"));
    }

    $startUtc = $start->setTimezone(new DateTimeZone("UTC"));
    $endUtc = $end->setTimezone(new DateTimeZone("UTC"));
    $stampUtc = new DateTimeImmutable("now", new DateTimeZone("UTC"));

    $vevent = $vcalendar->add("VEVENT");
    $uidLocal = strtolower($type) . "-" . $id;
    $uidSuffix = trim(strtolower($uidSuffix));
    if ($uidSuffix !== "") {
      $uidLocal .= "-" . preg_replace("/[^a-z0-9\-]+/", "-", $uidSuffix);
    }
    $vevent->UID = $uidLocal . "@" . $this->uidHost;
    $vevent->DTSTAMP = $stampUtc;
    $vevent->add("SUMMARY", $summary);
    $vevent->add("DTSTART", $startUtc);
    $vevent->add("DTEND", $endUtc);
    $locationValue = $this->normalizeLocationForIcs($location);
    if ($locationValue !== "") {
      $vevent->add("LOCATION", $locationValue);
    }
    if ($description !== "") {
      $vevent->add("DESCRIPTION", $description);
    }
    if ($url !== "") {
      $vevent->add("URL", $url);
    }
    $organizerRaw = $this->systemData->getCompany();
    $organizerName = trim(
      is_scalar($organizerRaw) || (is_object($organizerRaw) && method_exists($organizerRaw, "__toString"))
        ? (string) $organizerRaw
        : "",
    );
    if ($organizerName === "") {
      $organizerName = "BNote";
    }
    $vevent->add("ORGANIZER", "mailto:noreply@" . $this->uidHost, ["CN" => $organizerName]);

    return $vevent;
  }

  private function createAllDayEvent(
    VCalendar $vcalendar,
    string $type,
    int $id,
    string $summary,
    DateTimeImmutable $date,
    string $url,
  ): void {
    $nextDate = $date->add(new DateInterval("P1D"));
    $stampUtc = new DateTimeImmutable("now", new DateTimeZone("UTC"));
    $vevent = $vcalendar->add("VEVENT");
    $vevent->UID = strtolower($type) . "-" . $id . "@" . $this->uidHost;
    $vevent->DTSTAMP = $stampUtc;
    $vevent->add("SUMMARY", $summary);
    $vevent->add("DTSTART", $date->format("Ymd"), ["VALUE" => "DATE"]);
    $vevent->add("DTEND", $nextDate->format("Ymd"), ["VALUE" => "DATE"]);
    if ($url !== "") {
      $vevent->add("URL", $url);
    }
  }

  private function addAttendees(object $vevent, array $attendees): void
  {
    foreach ($attendees as $attendee) {
      $name = trim((string) ($attendee["name"] ?? "")) . " " . trim((string) ($attendee["surname"] ?? ""));
      $name = trim($name);
      $instrument = trim((string) ($attendee["instrument_name"] ?? ""));
      if ($instrument !== "") {
        $name .= " (" . $instrument . ")";
      }
      if ($name === "") {
        $name = $this->label("participant");
      }
      $email = trim((string) ($attendee["email"] ?? ""));
      if ($email === "" || strpos($email, "@") === false) {
        $email = "unknown+" . strtolower(preg_replace("/[^a-z0-9]+/i", "", $name)) . "@invalid.local";
      }
      $params = [
        "CN" => $name,
        "ROLE" => "REQ-PARTICIPANT",
      ];
      $partStat = strtoupper(trim((string) ($attendee["partstat"] ?? "")));
      if ($partStat !== "") {
        $params["PARTSTAT"] = $partStat;
      }
      $vevent->add("ATTENDEE", "mailto:" . strtolower($email), $params);
    }
  }

  private function buildRehearsalDescription(array $reh, string $locationDetails): string
  {
    $sections = [];
    $conductor = "";
    if (isset($reh["conductor"]) && $reh["conductor"] !== null && (int) $reh["conductor"] > 0) {
      $conductor = trim((string) $this->adp->getConductorname((int) $reh["conductor"]));
    }
    if ($conductor !== "") {
      $sections[] = $this->label("conductor") . ": " . $conductor;
    }

    $songs = $this->db->getSelection(
      "SELECT s.title, rs.notes FROM song s JOIN rehearsal_song rs ON rs.song = s.id WHERE rs.rehearsal = ? ORDER BY title",
      [["i", (int) $reh["id"]]],
    );
    $songTitles = [];
    foreach ($this->rows($songs) as $row) {
      $title = urldecode((string) ($row["title"] ?? ""));
      $songNote = trim((string) ($row["notes"] ?? ""));
      if ($songNote !== "") {
        $title .= " (" . $songNote . ")";
      }
      if ($title !== "") {
        $songTitles[] = $title;
      }
    }
    $sections[] = $this->label("practice") . ": " . (count($songTitles) > 0 ? implode(", ", $songTitles) : "-");

    if ($locationDetails !== "") {
      $sections[] = $this->label("location") . ":\n" . $locationDetails;
    }

    $notes = $this->normalizeNotesText((string) ($reh["notes"] ?? ""));
    if ($notes !== "") {
      $sections[] = $this->label("notes") . ":\n" . $notes;
    }

    return $this->joinSections($sections);
  }

  private function buildConcertDescription(array $concert, string $locationDetails): string
  {
    $sections = [];
    $outfit = trim((string) ($concert["outfit_name"] ?? ""));
    if ($outfit !== "") {
      $sections[] = $this->label("outfit") . ": " . $outfit;
    }

    $meetingTimeRaw = trim((string) ($concert["meetingtime"] ?? ""));
    if ($meetingTimeRaw !== "" && $meetingTimeRaw !== "0000-00-00 00:00:00") {
      $meeting = $this->formatLocalizedDateTime($meetingTimeRaw);
      if ($meeting !== "") {
        $sections[] = $this->label("meetingtime") . ": " . $meeting;
      }
    }

    if ($locationDetails !== "") {
      $sections[] = $this->label("location") . ":\n" . $locationDetails;
    }

    $notes = $this->normalizeNotesText((string) ($concert["notes"] ?? ""));
    if ($notes !== "") {
      $sections[] = $this->label("notes") . ":\n" . $notes;
    }

    $programId = (int) ($concert["program_id"] ?? 0);
    if ($programId > 0) {
      $songRows = $this->db->getSelection(
        "SELECT s.title FROM program_song ps JOIN song s ON ps.song = s.id WHERE ps.program = ? ORDER BY ps.rank ASC",
        [["i", $programId]],
      );
      $programLines = [];
      foreach ($this->rows($songRows) as $row) {
        $title = trim(urldecode((string) ($row["title"] ?? "")));
        if ($title !== "") {
          $programLines[] = "- " . $title;
        }
      }
      if (count($programLines) > 0) {
        $sections[] = $this->label("program") . ":\n" . implode("\n", $programLines);
      }
    }

    return $this->joinSections($sections);
  }

  private function getRehearsals(): array
  {
    $isAdmin = $this->isAdmin($this->userId);
    if ($isAdmin) {
      $rows = $this->db->getSelection(
        'SELECT r.id, r.begin, r.end, r.notes, r.status, r.conductor, l.name AS location_name, a.street AS location_street, a.city AS location_city
                 , a.zip AS location_zip
                 FROM rehearsal r
                 JOIN location l ON r.location = l.id
                 JOIN address a ON l.address = a.id
                 ORDER BY r.begin ASC',
      );
      return $this->rows($rows);
    }

    $rehearsalIds = array_unique(
      array_merge(
        $this->flattenSelection(
          $this->db->getSelection(
            "SELECT rehearsal FROM rehearsal_contact rc JOIN contact c ON rc.contact = c.id JOIN user u ON u.contact = c.id WHERE u.id = ?",
            [["i", $this->userId]],
          ),
          "rehearsal",
        ),
        $this->getRehearsalsFromPhases(),
      ),
    );
    if (count($rehearsalIds) === 0) {
      return [];
    }
    [$inSql, $params] = $this->buildInParams($rehearsalIds);
    $rows = $this->db->getSelection(
      'SELECT r.id, r.begin, r.end, r.notes, r.status, r.conductor, l.name AS location_name, a.street AS location_street, a.city AS location_city
             , a.zip AS location_zip
             FROM rehearsal r
             JOIN location l ON r.location = l.id
             JOIN address a ON l.address = a.id
             WHERE r.id IN (' .
        $inSql .
        ')
             ORDER BY r.begin ASC',
      $params,
    );
    return $this->rows($rows);
  }

  private function getConcerts(): array
  {
    $isAdmin = $this->isAdmin($this->userId);
    if ($isAdmin) {
      $rows = $this->db->getSelection(
        'SELECT c.id, c.title, c.begin, c.end, c.notes, c.meetingtime, c.status, c.program AS program_id,
                        l.name AS location_name, a.street AS location_street, a.city AS location_city, a.zip AS location_zip, o.name AS outfit_name
                 FROM concert c
                 LEFT JOIN location l ON c.location = l.id
                 LEFT JOIN address a ON l.address = a.id
                 LEFT JOIN outfit o ON c.outfit = o.id
                 ORDER BY c.begin ASC',
      );
      return $this->rows($rows);
    }

    $phaseConcertIds = $this->flattenSelection($this->getConcertsFromPhases(), "concert");
    $contactId = (int) $this->adp->getUserContact($this->userId);
    $directRows = $this->db->getSelection("SELECT concert FROM concert_contact WHERE contact = ?", [["i", $contactId]]);
    $directIds = $this->flattenSelection($directRows, "concert");
    $ids = array_unique(array_merge($phaseConcertIds, $directIds));
    if (count($ids) === 0) {
      return [];
    }
    [$inSql, $params] = $this->buildInParams($ids);
    $rows = $this->db->getSelection(
      'SELECT c.id, c.title, c.begin, c.end, c.notes, c.meetingtime, c.status, c.program AS program_id,
                    l.name AS location_name, a.street AS location_street, a.city AS location_city, a.zip AS location_zip, o.name AS outfit_name
             FROM concert c
             LEFT JOIN location l ON c.location = l.id
             LEFT JOIN address a ON l.address = a.id
             LEFT JOIN outfit o ON c.outfit = o.id
             WHERE c.id IN (' .
        $inSql .
        ')
             ORDER BY c.begin ASC',
      $params,
    );
    return $this->rows($rows);
  }

  private function getReservations(): array
  {
    $rows = $this->db->getSelection(
      'SELECT r.id, r.name, r.begin, r.end, r.notes, l.name AS location_name, a.street AS location_street, a.zip AS location_zip, a.city AS location_city
             FROM reservation r
             JOIN location l ON r.location = l.id
             JOIN address a ON l.address = a.id
             ORDER BY r.begin ASC',
    );
    return $this->rows($rows);
  }

  private function getAppointments(): array
  {
    $contactId = (int) $this->adp->getUserContact($this->userId);
    $rows = $this->db->getSelection(
      'SELECT DISTINCT a.id, a.name, a.begin, a.end, a.notes, l.name AS location_name, ad.street, ad.zip, ad.city
             FROM appointment a
             JOIN location l ON a.location = l.id
             JOIN address ad ON l.address = ad.id
             JOIN appointment_group ag ON a.id = ag.appointment
             JOIN contact_group cg ON ag.group = cg.group
             WHERE cg.contact = ?
             ORDER BY a.begin ASC',
      [["i", $contactId]],
    );
    return $this->rows($rows);
  }

  private function getTours(): array
  {
    $contact = $this->systemData->getUsersContact($this->userId);
    $contactId = is_array($contact) && !empty($contact["id"]) ? (int) $contact["id"] : 0;
    if ($contactId < 1) {
      return [];
    }
    $rows = $this->db->getSelection(
      "SELECT t.* FROM tour t JOIN tour_contact tc ON tc.tour = t.id WHERE tc.contact = ? ORDER BY t.start ASC",
      [["i", $contactId]],
    );
    return $this->rows($rows);
  }

  private function getRehearsalAttendees(int $rehearsalId): array
  {
    $selection = $this->db->getSelection(
      'SELECT c.id, c.surname, c.name, IF(c.share_email = 1, c.email, "") AS email, ru.participate, i.name AS instrument_name
             FROM rehearsal_user ru
             JOIN user u ON ru.user = u.id
             JOIN contact c ON u.contact = c.id
             LEFT JOIN instrument i ON c.instrument = i.id
             WHERE ru.rehearsal = ?',
      [["i", $rehearsalId]],
    );
    $rows = [];
    $knownContactIds = [];
    foreach ($this->rows($selection) as $row) {
      $knownContactIds[] = (int) $row["id"];
      $part = (int) ($row["participate"] ?? -1);
      $partStat = "NEEDS-ACTION";
      if ($part === 1) {
        $partStat = "ACCEPTED";
      } elseif ($part === 0) {
        $partStat = "DECLINED";
      } elseif ($part === 2) {
        $partStat = "TENTATIVE";
      }
      $row["partstat"] = $partStat;
      $rows[] = $row;
    }

    $params = [["i", $rehearsalId]];
    $excludeSql = "";
    if (count($knownContactIds) > 0) {
      [$inSql, $inParams] = $this->buildInParams($knownContactIds);
      $excludeSql = " AND rc.contact NOT IN (" . $inSql . ")";
      $params = array_merge($params, $inParams);
    }
    $pendingSel = $this->db->getSelection(
      'SELECT c.id, c.surname, c.name, IF(c.share_email = 1, c.email, "") AS email, i.name AS instrument_name
             FROM rehearsal_contact rc
             JOIN contact c ON rc.contact = c.id
             LEFT JOIN instrument i ON c.instrument = i.id
             WHERE rc.rehearsal = ?' . $excludeSql,
      $params,
    );
    foreach ($this->rows($pendingSel) as $row) {
      $row["partstat"] = "NEEDS-ACTION";
      $rows[] = $row;
    }
    return $rows;
  }

  private function getConcertAttendees(int $concertId): array
  {
    $selection = $this->db->getSelection(
      'SELECT c.id, c.surname, c.name, IF(c.share_email = 1, c.email, "") AS email, cu.participate, i.name AS instrument_name
             FROM concert_user cu
             JOIN user u ON cu.user = u.id
             JOIN contact c ON u.contact = c.id
             LEFT JOIN instrument i ON c.instrument = i.id
             WHERE cu.concert = ?',
      [["i", $concertId]],
    );
    $rows = [];
    $knownContactIds = [];
    foreach ($this->rows($selection) as $row) {
      $knownContactIds[] = (int) $row["id"];
      $part = (int) ($row["participate"] ?? -1);
      $partStat = "NEEDS-ACTION";
      if ($part === 1) {
        $partStat = "ACCEPTED";
      } elseif ($part === 0) {
        $partStat = "DECLINED";
      } elseif ($part === 2) {
        $partStat = "TENTATIVE";
      }
      $row["partstat"] = $partStat;
      $rows[] = $row;
    }

    $params = [["i", $concertId]];
    $excludeSql = "";
    if (count($knownContactIds) > 0) {
      [$inSql, $inParams] = $this->buildInParams($knownContactIds);
      $excludeSql = " AND cc.contact NOT IN (" . $inSql . ")";
      $params = array_merge($params, $inParams);
    }
    $pendingSel = $this->db->getSelection(
      'SELECT c.id, c.surname, c.name, IF(c.share_email = 1, c.email, "") AS email, i.name AS instrument_name
             FROM concert_contact cc
             JOIN contact c ON cc.contact = c.id
             LEFT JOIN instrument i ON c.instrument = i.id
             WHERE cc.concert = ?' . $excludeSql,
      $params,
    );
    foreach ($this->rows($pendingSel) as $row) {
      $row["partstat"] = "NEEDS-ACTION";
      $rows[] = $row;
    }
    return $rows;
  }

  private function getRehearsalsFromPhases(): array
  {
    $phases = $this->adp->getUsersPhases($this->userId);
    if (!is_array($phases) || count($phases) === 0) {
      return [];
    }
    [$inSql, $params] = $this->buildInParams(array_map("intval", $phases));
    return $this->flattenSelection(
      $this->db->getSelection(
        "SELECT rehearsal FROM rehearsalphase_rehearsal WHERE rehearsalphase IN (" . $inSql . ")",
        $params,
      ),
      "rehearsal",
    );
  }

  private function getConcertsFromPhases(): array
  {
    $phases = $this->adp->getUsersPhases($this->userId);
    if (!is_array($phases) || count($phases) === 0) {
      return [];
    }
    [$inSql, $params] = $this->buildInParams(array_map("intval", $phases));
    return $this->rows(
      $this->db->getSelection(
        "SELECT concert FROM rehearsalphase_concert WHERE rehearsalphase IN (" . $inSql . ")",
        $params,
      ),
    );
  }

  private function buildEntityUrl(string $type, int $id): string
  {
    if ($id < 1) {
      return $this->appBaseUrl . "/dashboard";
    }
    if ($type === "tour") {
      return $this->appBaseUrl . "/dashboard";
    }
    return $this->appBaseUrl .
      "/entity?" .
      http_build_query([
        "type" => $type,
        "id" => (string) $id,
      ]);
  }

  private function composeLocationDetails(string $name, string $street, string $city, string $zip): string
  {
    $name = trim($name);
    $address = $this->composeAddress($street, $city, $zip);
    $parts = [];
    if ($name !== "") {
      $parts[] = $name;
    }
    if ($address !== "") {
      $parts[] = $address;
    }
    return implode("\n", $parts);
  }

  private function composeAddress(string $street, string $city, string $zip): string
  {
    $street = trim($street);
    $city = trim($city);
    $zip = trim($zip);
    $line = trim($street . ($zip !== "" ? ", " . $zip : "") . ($city !== "" ? " " . $city : ""));
    return $line;
  }

  private function normalizeLocationForIcs(string $location): string
  {
    $location = str_replace(["\r\n", "\r", "\n"], " ", trim($location));
    $location = preg_replace("/\s+/u", " ", $location) ?? $location;
    return trim($location);
  }

  private function parseDbDateTime(string $raw): ?DateTimeImmutable
  {
    $raw = trim($raw);
    if ($raw === "" || $raw === "0000-00-00 00:00:00" || $raw === "0000-00-00") {
      return null;
    }
    $tz = new DateTimeZone(MailLocaleDateTime::defaultTimezone());
    foreach (["Y-m-d H:i:s", "Y-m-d H:i", "Y-m-d"] as $fmt) {
      $dt = DateTimeImmutable::createFromFormat($fmt, $raw, $tz);
      if ($dt instanceof DateTimeImmutable) {
        return $dt;
      }
    }
    try {
      return new DateTimeImmutable($raw, $tz);
    } catch (Throwable $e) {
      return null;
    }
  }

  private function detectLang(): string
  {
    $lang = "";
    if (method_exists($this->systemData, "getLang")) {
      $lang = strtolower(trim((string) $this->systemData->getLang()));
    }
    return in_array($lang, ["de", "en", "es", "fr"], true) ? $lang : "en";
  }

  private function detectCompanyName(): string
  {
    $raw = $this->systemData->getCompany();
    $name = trim(is_scalar($raw) || (is_object($raw) && method_exists($raw, "__toString")) ? (string) $raw : "");
    return $name;
  }

  private function detectAppBaseUrl(): string
  {
    $base = trim(MailEnv::nextgenPublicBaseUrl());
    if ($base !== "") {
      return rtrim($base, "/");
    }

    $scheme = "https";
    if (
      (isset($_SERVER["HTTPS"]) && strtolower((string) $_SERVER["HTTPS"]) !== "off" && $_SERVER["HTTPS"] !== "") ||
      (isset($_SERVER["REQUEST_SCHEME"]) && strtolower((string) $_SERVER["REQUEST_SCHEME"]) === "https")
    ) {
      $scheme = "https";
    } elseif (isset($_SERVER["REQUEST_SCHEME"]) && strtolower((string) $_SERVER["REQUEST_SCHEME"]) === "http") {
      $scheme = "http";
    }
    $host = trim((string) ($_SERVER["HTTP_HOST"] ?? "localhost"));
    $prefix = trim(MailEnv::nextgenAppPathPrefix());
    return rtrim($scheme . "://" . $host . $prefix, "/");
  }

  private function detectUidHost(): string
  {
    $candidate = trim((string) ($_SERVER["HTTP_HOST"] ?? ""));
    if ($candidate !== "") {
      return preg_replace('/:\d+$/', "", $candidate) ?: "bnote.local";
    }
    $sys = trim((string) ($this->systemData->getSystemURL() ?? ""));
    if ($sys !== "") {
      $host = parse_url($sys, PHP_URL_HOST);
      if (is_string($host) && $host !== "") {
        return $host;
      }
    }
    return "bnote.local";
  }

  private function isAdmin(int $uid): bool
  {
    return $uid > 0 && ($this->systemData->isUserSuperUser($uid) || $this->systemData->isUserMemberGroup(1, $uid));
  }

  private function rows($selection): array
  {
    if (!is_array($selection) || count($selection) < 2) {
      return [];
    }
    return array_values(array_slice($selection, 1));
  }

  private function flattenSelection($selection, string $key): array
  {
    $vals = [];
    foreach ($this->rows($selection) as $row) {
      if (isset($row[$key])) {
        $vals[] = (int) $row[$key];
      }
    }
    return $vals;
  }

  /**
   * @param list<int> $ids
   * @return array{0:string,1:list<array{0:string,1:int}>}
   */
  private function buildInParams(array $ids): array
  {
    $ids = array_values(array_unique(array_map("intval", $ids)));
    $inSql = implode(",", array_fill(0, count($ids), "?"));
    $params = [];
    foreach ($ids as $id) {
      $params[] = ["i", $id];
    }
    return [$inSql, $params];
  }

  private function label(string $key): string
  {
    $labels = [
      "de" => [
        "calendar" => "Kalender",
        "rehearsal" => "Probe",
        "concert" => "Konzert",
        "reservation" => "Reservierung",
        "appointment" => "Termin",
        "tour" => "Tour",
        "task" => "Aufgabe",
        "birthday" => "Geburtstag",
        "participant" => "Teilnehmer",
        "conductor" => "Dirigent",
        "practice" => "Bitte folgende Stücke üben",
        "notes" => "Notizen",
        "outfit" => "Outfit",
        "meetingtime" => "Treffpunkt",
        "program" => "Programm",
        "location" => "Ort",
      ],
      "en" => [
        "calendar" => "Calendar",
        "rehearsal" => "Rehearsal",
        "concert" => "Concert",
        "reservation" => "Reservation",
        "appointment" => "Appointment",
        "tour" => "Tour",
        "task" => "Task",
        "birthday" => "Birthday",
        "participant" => "Participant",
        "conductor" => "Conductor",
        "practice" => "Please practice",
        "notes" => "Notes",
        "outfit" => "Outfit",
        "meetingtime" => "Meeting time",
        "program" => "Program",
        "location" => "Location",
      ],
      "es" => [
        "calendar" => "Calendario",
        "rehearsal" => "Ensayo",
        "concert" => "Concierto",
        "reservation" => "Reserva",
        "appointment" => "Cita",
        "tour" => "Gira",
        "task" => "Tarea",
        "birthday" => "Cumpleaños",
        "participant" => "Participante",
        "conductor" => "Director",
        "practice" => "Por favor practicar",
        "notes" => "Notas",
        "outfit" => "Vestimenta",
        "meetingtime" => "Hora de encuentro",
        "program" => "Programa",
        "location" => "Ubicación",
      ],
      "fr" => [
        "calendar" => "Calendrier",
        "rehearsal" => "Répétition",
        "concert" => "Concert",
        "reservation" => "Réservation",
        "appointment" => "Rendez-vous",
        "tour" => "Tournée",
        "task" => "Tâche",
        "birthday" => "Anniversaire",
        "participant" => "Participant",
        "conductor" => "Chef",
        "practice" => "Veuillez pratiquer",
        "notes" => "Notes",
        "outfit" => "Tenue",
        "meetingtime" => "Heure de rendez-vous",
        "program" => "Programme",
        "location" => "Lieu",
      ],
    ];
    return $labels[$this->lang][$key] ?? ($labels["en"][$key] ?? $key);
  }

  private function getTasks(): array
  {
    $rows = $this->adp->getUserTasks($this->userId);
    return $this->rows($rows);
  }

  private function getBirthdays(): array
  {
    $rows = $this->db->getSelection(
      'SELECT c.id, c.name, c.surname, c.birthday, c.share_birthday, u.isActive
             FROM contact c
             LEFT JOIN user u ON u.contact = c.id
             ORDER BY c.surname, c.name',
    );
    $out = [];
    foreach ($this->rows($rows) as $row) {
      $shareBirthday = isset($row["share_birthday"]) ? (int) $row["share_birthday"] : 1;
      if ($shareBirthday !== 1) {
        continue;
      }
      if (isset($row["isActive"]) && $row["isActive"] !== null && (int) $row["isActive"] !== 1) {
        continue;
      }
      $birthdayRaw = trim((string) ($row["birthday"] ?? ""));
      if ($birthdayRaw === "" || $birthdayRaw === "-" || $birthdayRaw === "0000-00-00") {
        continue;
      }
      $out[] = $row;
    }
    return $out;
  }

  private function birthdayInCurrentYear(string $birthdayRaw): ?DateTimeImmutable
  {
    $birthdayRaw = trim($birthdayRaw);
    if ($birthdayRaw === "" || $birthdayRaw === "-" || $birthdayRaw === "0000-00-00") {
      return null;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdayRaw)) {
      return null;
    }
    $thisYear = (int) date("Y");
    $md = substr($birthdayRaw, 5);
    $candidate = sprintf("%04d-%s", $thisYear, $md);
    $tz = new DateTimeZone(MailLocaleDateTime::defaultTimezone());
    $dt = DateTimeImmutable::createFromFormat("Y-m-d", $candidate, $tz);
    return $dt instanceof DateTimeImmutable ? $dt : null;
  }

  private function appendLine(string $text, string $line): string
  {
    $line = trim($line);
    if ($line === "") {
      return $text;
    }
    if (trim($text) === "") {
      return $line;
    }
    return $text . "\n" . $line;
  }

  private function joinSections(array $sections): string
  {
    $clean = [];
    foreach ($sections as $section) {
      $section = trim((string) $section);
      if ($section !== "") {
        $clean[] = $section;
      }
    }
    return implode("\n\n", $clean);
  }

  private function normalizeNotesText(string $raw): string
  {
    $raw = trim($raw);
    if ($raw === "" || $raw === "-") {
      return "";
    }

    $fromEditorJson = $this->extractEditorJsText($raw);
    if ($fromEditorJson !== null) {
      return $fromEditorJson;
    }

    return $this->htmlToPlain($raw);
  }

  private function extractEditorJsText(string $raw): ?string
  {
    $json = json_decode($raw, true);
    if (!is_array($json) || !isset($json["blocks"]) || !is_array($json["blocks"])) {
      return null;
    }
    $lines = [];
    foreach ($json["blocks"] as $block) {
      if (!is_array($block)) {
        continue;
      }
      $type = (string) ($block["type"] ?? "");
      $data = isset($block["data"]) && is_array($block["data"]) ? $block["data"] : [];
      if (($type === "paragraph" || $type === "header") && isset($data["text"])) {
        $txt = $this->htmlToPlain((string) $data["text"]);
        if ($txt !== "") {
          $lines[] = $txt;
        }
      } elseif (($type === "list" || $type === "checklist") && isset($data["items"]) && is_array($data["items"])) {
        foreach ($data["items"] as $item) {
          $txt = $this->htmlToPlain(is_string($item) ? $item : (is_array($item) ? (string) ($item["text"] ?? "") : ""));
          if ($txt !== "") {
            $lines[] = "- " . $txt;
          }
        }
      } elseif ($type === "quote" && isset($data["text"])) {
        $txt = $this->htmlToPlain((string) $data["text"]);
        if ($txt !== "") {
          $lines[] = '"' . $txt . '"';
        }
      }
    }
    $out = trim(implode("\n", $lines));
    return $out === "" ? "" : $out;
  }

  private function htmlToPlain(string $value): string
  {
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = preg_replace("/<\s*br\s*\/?>/i", "\n", $value) ?? $value;
    $value = strip_tags($value);
    $value = html_entity_decode($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, "UTF-8");
    $value = preg_replace("/[ \t]+/u", " ", $value) ?? $value;
    $value = preg_replace("/\n{3,}/u", "\n\n", $value) ?? $value;
    return trim($value);
  }

  private function formatLocalizedDateTime(string $raw): string
  {
    $dt = $this->parseDbDateTime($raw);
    if ($dt === null) {
      return "";
    }
    return MailLocaleDateTime::formatDateTimeShort($dt, $this->lang);
  }
}
