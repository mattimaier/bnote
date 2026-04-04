#!/usr/bin/env node

import { execSync } from "node:child_process";
import { mkdirSync, readFileSync, writeFileSync } from "node:fs";
import { resolve } from "node:path";

const frontendDir = process.cwd();
const repoRoot = resolve(frontendDir, "..");
const outputPath = resolve(repoRoot, "api/config/beta-changelog.json");
const overridePath = resolve(repoRoot, "docs/changelog-beta-overrides.json");
const packageJsonPath = resolve(frontendDir, "package.json");
const maxEntries = Number.parseInt(process.env.BETA_CHANGELOG_MAX ?? "200", 10) || 200;

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

function normalizeTitle(value) {
  const v = String(value ?? "").trim();
  if (!v) return "";
  return v.replace(/\s+/g, " ");
}

function normalizeChangeType(value) {
  const raw = String(value ?? "").trim().toLowerCase();
  if (raw === "added" || raw === "fixed" || raw === "changed" || raw === "removed") {
    return raw;
  }
  return "";
}

function stripBugMarkers(subject) {
  const s = normalizeTitle(subject);
  if (!s) return "";
  const removedParen = s.replace(/\(\s*BUG-\d{8}-\d{6}-[a-z0-9]+\s*\)/gi, "");
  const removedStandalone = removedParen.replace(/\bBUG-\d{8}-\d{6}-[a-z0-9]+\b/gi, "");
  return normalizeTitle(removedStandalone.replace(/\s+[|:-]\s*$/g, ""));
}

function resolveOverrideMeta(overrides, bugId) {
  const candidate = overrides?.[bugId];
  if (typeof candidate === "string") {
    return {
      title: normalizeTitle(candidate),
      changeType: "",
    };
  }
  if (candidate && typeof candidate === "object") {
    const overrideType = normalizeChangeType(candidate.changeType ?? candidate.type);
    if (typeof candidate.title === "string") {
      return {
        title: normalizeTitle(candidate.title),
        changeType: overrideType,
      };
    }
    if (candidate.title && typeof candidate.title === "object") {
      const localized = candidate.title.en ?? candidate.title.de ?? candidate.title.es ?? candidate.title.fr;
      if (typeof localized === "string") {
        return {
          title: normalizeTitle(localized),
          changeType: overrideType,
        };
      }
    }
  }
  return {
    title: "",
    changeType: "",
  };
}

function extractConventionalCommit(subject) {
  const s = normalizeTitle(subject);
  if (!s) return { type: "", text: "" };
  const m = s.match(/^([a-z]+)(?:\([^)]+\))?!?:\s*(.+)$/i);
  if (!m) return { type: "", text: s };
  return {
    type: String(m[1] ?? "").toLowerCase(),
    text: normalizeTitle(m[2] ?? ""),
  };
}

function inferChangeType(subject, preferred = "") {
  if (preferred) return preferred;
  const { type, text } = extractConventionalCommit(subject);
  const scan = `${type} ${text}`.toLowerCase();
  if (type === "fix" || type === "hotfix" || /\b(fix|fixed|bug|bugfix)\b/.test(scan)) return "fixed";
  if (type === "feat" || /\b(add|added|new|create|created|introduce|introduced)\b/.test(scan)) return "added";
  if (/\b(remove|removed|delete|deleted|drop|dropped|deprecat|retire)\b/.test(scan)) return "removed";
  return "changed";
}

function stripLeadingVerbByType(title, changeType) {
  const t = normalizeTitle(title);
  if (!t) return "";
  if (changeType === "fixed") {
    return normalizeTitle(t.replace(/^(fix|fixed|bugfix)\s+/i, ""));
  }
  if (changeType === "added") {
    return normalizeTitle(t.replace(/^(add|added|new|introduce|introduced|create|created)\s+/i, ""));
  }
  if (changeType === "removed") {
    return normalizeTitle(t.replace(/^(remove|removed|delete|deleted|drop|dropped)\s+/i, ""));
  }
  if (changeType === "changed") {
    return normalizeTitle(t.replace(/^(change|changed|update|updated|improve|improved|refactor|refactored)\s+/i, ""));
  }
  return t;
}

function capitalizeFirst(value) {
  const s = normalizeTitle(value);
  if (!s) return "";
  return `${s.charAt(0).toUpperCase()}${s.slice(1)}`;
}

function normalizeChangelogTitle(rawTitle, changeType) {
  const stripped = stripLeadingVerbByType(rawTitle, changeType);
  return capitalizeFirst(stripped || rawTitle);
}

function readVersion() {
  try {
    const pkg = readJsonFile(packageJsonPath, {});
    const v = String(pkg.version ?? "").trim();
    return v || "unknown";
  } catch {
    return "unknown";
  }
}

const overrides = readJsonFile(overridePath, {});
const fullCommit = safeExec(`git -C "${repoRoot}" rev-parse HEAD`) || "unknown";
const shortCommit = safeExec(`git -C "${repoRoot}" rev-parse --short=12 HEAD`) || "unknown";
const gitLog = safeExec(
  `git -C "${repoRoot}" log --date=iso-strict --pretty=format:%H%x09%h%x09%cI%x09%s`
);

const entries = [];
const seenBugIds = new Set();
const lines = gitLog ? gitLog.split("\n") : [];
for (const line of lines) {
  if (entries.length >= maxEntries) break;
  const [commit = "", short = "", date = "", ...subjectParts] = line.split("\t");
  const subject = normalizeTitle(subjectParts.join("\t"));
  if (!subject) continue;
  const matches = subject.match(/BUG-\d{8}-\d{6}-[a-z0-9]+/gi);
  if (!matches || matches.length === 0) continue;
  for (const rawBugId of matches) {
    if (entries.length >= maxEntries) break;
    const bugId = rawBugId.toUpperCase();
    if (seenBugIds.has(bugId)) continue;
    seenBugIds.add(bugId);
    const overrideMeta = resolveOverrideMeta(overrides, bugId);
    const { text: conventionalText } = extractConventionalCommit(subject);
    const cleaned = stripBugMarkers(conventionalText || subject);
    const changeType = inferChangeType(subject, overrideMeta.changeType);
    const chosenRawTitle = overrideMeta.title || cleaned || subject;
    entries.push({
      bugId,
      changeType,
      title: normalizeChangelogTitle(chosenRawTitle, changeType),
      subject,
      commit: commit || "unknown",
      shortCommit: short || "unknown",
      date: date || null,
    });
  }
}

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

mkdirSync(resolve(repoRoot, "api/config"), { recursive: true });
writeFileSync(outputPath, `${JSON.stringify(payload, null, 2)}\n`, "utf8");

console.log(
  `generate-beta-changelog: wrote ${entries.length} entries to ${outputPath}`
);
