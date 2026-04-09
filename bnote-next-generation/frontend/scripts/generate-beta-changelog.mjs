#!/usr/bin/env node

import { execSync } from "node:child_process";
import { existsSync, mkdirSync, readFileSync, writeFileSync } from "node:fs";
import { resolve } from "node:path";

const frontendDir = process.cwd();
const repoRoot = resolve(frontendDir, "..");
const outputPath = resolve(repoRoot, "api/config/beta-changelog.json");
const overridePath = resolve(repoRoot, "docs/changelog-beta-overrides.json");
const packageJsonPath = resolve(frontendDir, "package.json");
const maxEntries = Number.parseInt(process.env.BETA_CHANGELOG_MAX ?? "200", 10) || 200;
const betaStartIso = String(process.env.BETA_CHANGELOG_SINCE ?? "2026-04-02T00:00:00+02:00").trim();
const betaStartMs = Number.isNaN(Date.parse(betaStartIso)) ? 0 : Date.parse(betaStartIso);

function safeExec(command) {
  try {
    return execSync(command, { encoding: "utf8", stdio: ["ignore", "pipe", "ignore"] }).trim();
  } catch {
    return "";
  }
}

function readJsonFile(path, fallback) {
  try {
    const raw = readFileSync(path, "utf8");
    const parsed = JSON.parse(raw);
    return parsed ?? fallback;
  } catch {
    return fallback;
  }
}

function normalizeText(value) {
  const v = String(value ?? "").trim();
  if (!v) return "";
  return v.replace(/\s+/g, " ");
}

function normalizeType(value) {
  const v = normalizeText(value).toLowerCase();
  if (v === "added" || v === "fixed" || v === "changed" || v === "removed") return v;
  return "changed";
}

function extractConventionalCommit(subject) {
  const s = normalizeText(subject);
  if (!s) return { type: "", text: "" };
  const m = s.match(/^([a-z]+)(?:\([^)]+\))?!?:\s*(.+)$/i);
  if (!m) return { type: "", text: s };
  return {
    type: String(m[1] ?? "").toLowerCase(),
    text: normalizeText(m[2] ?? ""),
  };
}

function stripBugMarkers(subject) {
  const s = normalizeText(subject);
  if (!s) return "";
  const removedParen = s.replace(/\(\s*BUG-\d{8}-\d{6}-[a-z0-9]+\s*\)/gi, "");
  const removedStandalone = removedParen.replace(/\bBUG-\d{8}-\d{6}-[a-z0-9]+\b/gi, "");
  return normalizeText(removedStandalone.replace(/\s+[|:-]\s*$/g, ""));
}

function cleanBugTitle(subject) {
  const { text: conventionalText } = extractConventionalCommit(subject);
  const cleaned = stripBugMarkers(conventionalText || subject);
  const noFixPrefix = cleaned.replace(/^(fix|fixed|bugfix)\s+/i, "");
  const n = normalizeText(noFixPrefix || cleaned || subject);
  return n ? `${n.charAt(0).toUpperCase()}${n.slice(1)}` : "Update";
}

function normalizeCuratedEntries(input) {
  if (!Array.isArray(input)) return [];
  const out = [];
  for (const row of input) {
    if (!row || typeof row !== "object") continue;
    const title = normalizeText(row.title);
    if (!title) continue;
    const bugIdRaw = normalizeText(row.bugId).toUpperCase();
    out.push({
      title,
      changeType: normalizeType(row.changeType ?? row.type),
      date: normalizeText(row.date),
      bugId: bugIdRaw || null,
    });
  }
  return out;
}

function parseDateMs(value) {
  const ms = Date.parse(String(value ?? ""));
  return Number.isNaN(ms) ? 0 : ms;
}

function readVersion() {
  try {
    const pkg = readJsonFile(packageJsonPath, {});
    const v = normalizeText(pkg.version);
    return v || "unknown";
  } catch {
    return "unknown";
  }
}

const overrides = readJsonFile(overridePath, {});
const curatedEntries = normalizeCuratedEntries(overrides?.entries ?? []);

const seenBugIds = new Set();
const bugEntries = [];
const gitLog = safeExec(
  `git -C "${repoRoot}" log --date=iso-strict --pretty=format:%H%x09%h%x09%cI%x09%s`
);
const lines = gitLog ? gitLog.split("\n") : [];
for (const line of lines) {
  const [, , date = "", ...subjectParts] = line.split("\t");
  const subject = normalizeText(subjectParts.join("\t"));
  if (!subject) continue;
  const commitDateMs = parseDateMs(date);
  if (betaStartMs > 0 && commitDateMs > 0 && commitDateMs < betaStartMs) continue;

  const matches = subject.match(/BUG-\d{8}-\d{6}-[a-z0-9]+/gi);
  if (!matches || matches.length === 0) continue;

  for (const rawBugId of matches) {
    if (bugEntries.length >= maxEntries) break;
    const bugId = rawBugId.toUpperCase();
    if (seenBugIds.has(bugId)) continue;
    seenBugIds.add(bugId);
    bugEntries.push({
      title: cleanBugTitle(subject),
      changeType: "fixed",
      date: normalizeText(date),
      bugId,
    });
  }
}

const dedupeKey = (entry) =>
  `${entry.bugId ?? ""}::${entry.changeType}::${normalizeText(entry.title).toLowerCase()}::${entry.date ?? ""}`;

const deduped = [];
const seen = new Set();
for (const entry of [...curatedEntries, ...bugEntries].sort((a, b) => parseDateMs(b.date) - parseDateMs(a.date))) {
  const key = dedupeKey(entry);
  if (seen.has(key)) continue;
  seen.add(key);
  deduped.push(entry);
  if (deduped.length >= maxEntries) break;
}

const entries = deduped;

const shortCommit = safeExec(`git -C "${repoRoot}" rev-parse --short=12 HEAD`) || "unknown";
const fullCommit = safeExec(`git -C "${repoRoot}" rev-parse HEAD`) || "unknown";
const buildVersion = String(process.env.NEXT_PUBLIC_APP_VERSION ?? "").trim() || readVersion();
const buildId = String(process.env.NEXT_PUBLIC_APP_BUILD_ID ?? "").trim() || shortCommit;
const buildCommit = String(process.env.NEXT_PUBLIC_APP_COMMIT ?? "").trim() || shortCommit;
const buildTime = String(process.env.NEXT_PUBLIC_APP_BUILD_TIME ?? "").trim() || new Date().toISOString();
const releaseId = `${buildVersion}@${buildCommit}`;

const payload = {
  releaseId,
  generatedAt: new Date().toISOString(),
  source: "hybrid",
  build: {
    version: buildVersion,
    buildId,
    commit: buildCommit,
    fullCommit,
    buildTime,
  },
  entries,
};

const shouldWrite =
  String(process.env.BETA_CHANGELOG_WRITE ?? "").trim() === "1" ||
  String(process.env.CI ?? "").trim().toLowerCase() === "true" ||
  !existsSync(outputPath);

if (!shouldWrite) {
  console.log("generate-beta-changelog: local mode; keeping committed api/config/beta-changelog.json unchanged");
  process.exit(0);
}

mkdirSync(resolve(repoRoot, "api/config"), { recursive: true });
writeFileSync(outputPath, `${JSON.stringify(payload, null, 2)}\n`, "utf8");

console.log(`generate-beta-changelog: wrote ${entries.length} entries to ${outputPath}`);
