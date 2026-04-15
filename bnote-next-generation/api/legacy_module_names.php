<?php
/**
 * Legacy module name adapter.
 *
 * Keeps old-core module identifiers in one boundary while allowing
 * Next Generation code to use English constants everywhere else.
 */

final class LegacyModuleKey
{
  public const DASHBOARD = "dashboard";
  public const STATS = "stats";
  public const REHEARSALS = "rehearsals";
  public const CONCERTS = "concerts";
  public const USERS = "users";
  public const CONTACTS = "contacts";
  public const MEMBERS = "members";
  public const SHARE = "share";
  public const LOCATIONS = "locations";
  public const EQUIPMENT = "equipment";
  public const OUTFITS = "outfits";
  public const REPERTOIRE = "repertoire";
  public const VOTING = "voting";
  public const NEWS = "news";
  public const TASKS = "tasks";
  public const COMMUNICATION = "communication";
  public const CALENDAR = "calendar";
  public const WRAPPED = "wrapped";
}

/**
 * @return array<string, string>
 */
function legacyModuleNameMap(): array
{
  return [
    LegacyModuleKey::DASHBOARD => "Start",
    LegacyModuleKey::STATS => "Stats",
    LegacyModuleKey::REHEARSALS => "Proben",
    LegacyModuleKey::CONCERTS => "Konzerte",
    LegacyModuleKey::USERS => "User",
    LegacyModuleKey::CONTACTS => "Kontakte",
    LegacyModuleKey::MEMBERS => "Mitspieler",
    LegacyModuleKey::SHARE => "Share",
    LegacyModuleKey::LOCATIONS => "Locations",
    LegacyModuleKey::EQUIPMENT => "Equipment",
    LegacyModuleKey::OUTFITS => "Outfits",
    LegacyModuleKey::REPERTOIRE => "Repertoire",
    LegacyModuleKey::VOTING => "Abstimmung",
    LegacyModuleKey::NEWS => "Nachrichten",
    LegacyModuleKey::TASKS => "Aufgaben",
    LegacyModuleKey::COMMUNICATION => "Kommunikation",
    LegacyModuleKey::CALENDAR => "Calendar",
    LegacyModuleKey::WRAPPED => "Wrapped",
  ];
}

function getLegacyModuleName(string $key): string
{
  $map = legacyModuleNameMap();
  if (!isset($map[$key])) {
    throw new InvalidArgumentException("Unknown legacy module key: " . $key);
  }

  return $map[$key];
}

/**
 * @param object $systemData Systemdata instance from legacy core.
 */
function getLegacyModuleId(object $systemData, string $key): int
{
  return (int) $systemData->getModuleId(getLegacyModuleName($key));
}
