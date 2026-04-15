#!/usr/bin/env node
import fs from "node:fs/promises";
import path from "node:path";
import { execFile as execFileCb } from "node:child_process";
import { promisify } from "node:util";

const execFile = promisify(execFileCb);

const mode = process.argv.includes("--check") ? "check" : "write";
const repoRoot = path.resolve(process.cwd(), "..");

const EXCLUDE_BASENAMES = new Set(["package-lock.json"]);

function sortJsonValue(value) {
  if (Array.isArray(value)) {
    return value.map(sortJsonValue);
  }
  if (value && typeof value === "object") {
    const out = {};
    for (const key of Object.keys(value).sort((a, b) => a.localeCompare(b))) {
      out[key] = sortJsonValue(value[key]);
    }
    return out;
  }
  return value;
}

const { stdout } = await execFile("git", ["-C", repoRoot, "ls-files", "*.json"]);
const files = stdout
  .split("\n")
  .map((line) => line.trim())
  .filter(Boolean)
  .filter((file) => !EXCLUDE_BASENAMES.has(path.basename(file)));

const changed = [];
for (const relFile of files) {
  const absFile = path.join(repoRoot, relFile);
  const raw = await fs.readFile(absFile, "utf8");
  let parsed;
  try {
    parsed = JSON.parse(raw);
  } catch (error) {
    throw new Error(`Invalid JSON in ${relFile}: ${error.message}`);
  }
  const sorted = sortJsonValue(parsed);
  const formatted = `${JSON.stringify(sorted, null, 2)}\n`;
  if (formatted !== raw) {
    changed.push(relFile);
    if (mode === "write") {
      await fs.writeFile(absFile, formatted, "utf8");
    }
  }
}

if (mode === "check" && changed.length > 0) {
  console.error("JSON sort/format violations:");
  for (const file of changed) {
    console.error(`- ${file}`);
  }
  process.exit(1);
}

if (changed.length === 0) {
  console.log("JSON files are sorted and pretty-printed.");
} else if (mode === "write") {
  console.log(`Sorted and formatted ${changed.length} JSON files.`);
}
