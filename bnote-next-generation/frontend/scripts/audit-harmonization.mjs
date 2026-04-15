#!/usr/bin/env node
import path from "node:path";
import fs from "node:fs/promises";
import { execFile as execFileCb } from "node:child_process";
import { promisify } from "node:util";

const execFile = promisify(execFileCb);
const repoRoot = path.resolve(process.cwd(), "..");

const violations = [];

const forbiddenContractPatterns = [
  {
    regex: /['"`]kontaktdaten['"`]|module=kontaktdaten|\/modules\/kontaktdaten\.php|kontaktdaten-api/i,
    reason: "German Next Gen contract name is disallowed (use profile).",
  },
  { regex: /components\/Calendar\//, reason: "Uppercase folder path detected (use components/calendar)." },
  {
    regex: /api\/modules\/kontaktdaten\.php/,
    reason: "Legacy Next Gen module filename is disallowed (use profile.php).",
  },
];

const forbiddenLegacyLookup =
  /getModuleId\('(?:Proben|Konzerte|Kontakte|Mitspieler|Abstimmung|Aufgaben|Nachrichten|Kommunikation|Start|Calendar|Wrapped|Share|Repertoire|Outfits|Equipment|Locations)'\)/;

const { stdout: fileListStdout } = await execFile("git", ["-C", repoRoot, "ls-files"]);
const files = fileListStdout
  .split("\n")
  .map((line) => line.trim())
  .filter(Boolean);

for (const relFile of files) {
  if (!/\.(php|ts|tsx|js|mjs|cjs|md|json)$/.test(relFile)) continue;
  if (relFile.startsWith("lang/")) continue;
  if (relFile.startsWith("frontend/components/legal/")) continue;
  if (relFile.startsWith("frontend/components/privacy/")) continue;
  if (relFile.startsWith("frontend/components/imprint/")) continue;
  if (relFile === "api/legacy_module_names.php") continue;

  const abs = path.join(repoRoot, relFile);
  let content;
  try {
    content = await fs.readFile(abs, "utf8");
  } catch (error) {
    if (error && error.code === "ENOENT") {
      continue;
    }
    throw error;
  }

  for (const { regex, reason } of forbiddenContractPatterns) {
    if (regex.test(content)) {
      violations.push(`${relFile}: ${reason}`);
    }
  }
  if (relFile.startsWith("api/") && forbiddenLegacyLookup.test(content)) {
    violations.push(
      `${relFile}: direct legacy getModuleId('...') call detected; use getLegacyModuleId(..., LegacyModuleKey::...).`
    );
  }
}

for (const relFile of files) {
  try {
    await fs.access(path.join(repoRoot, relFile));
  } catch (error) {
    if (error && error.code === "ENOENT") {
      continue;
    }
    throw error;
  }
  const segments = relFile.split("/").slice(0, -1);
  for (const seg of segments) {
    if (/^[A-Z]/.test(seg)) {
      violations.push(
        `${relFile}: directory segment "${seg}" starts with uppercase; use lowercase/kebab-case directories.`
      );
      break;
    }
  }
}

if (violations.length > 0) {
  console.error("Harmonization audit failed:");
  for (const v of violations) {
    console.error(`- ${v}`);
  }
  process.exit(1);
}

console.log("Harmonization audit passed.");
