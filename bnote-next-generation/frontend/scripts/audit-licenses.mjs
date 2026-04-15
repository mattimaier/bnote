#!/usr/bin/env node
import fs from "node:fs/promises";
import path from "node:path";

const repoRoot = path.resolve(process.cwd(), "..");
const outDir = path.join(repoRoot, "docs", "reports");
const outFile = path.join(outDir, "license-audit.json");

const npmAllow = [
  /^MIT$/i,
  /^ISC$/i,
  /^BSD(-\d-Clause)?$/i,
  /^Apache-2\.0$/i,
  /^MPL-2\.0$/i,
  /^LGPL-[\d.]+(-only|-or-later)?$/i,
  /^CC0-1\.0$/i,
  /^0BSD$/i,
  /^Python-2\.0$/i,
  /^BlueOak-1\.0\.0$/i,
  /^Unlicense$/i,
  /^Zlib$/i,
];

function isAllowed(license) {
  const parts = String(license)
    .split(/\s+AND\s+|\s+OR\s+/i)
    .map((p) => p.trim().replace(/[()]/g, ""))
    .filter(Boolean);
  return parts.length > 0 && parts.every((p) => npmAllow.some((re) => re.test(p)));
}

const packageLock = JSON.parse(await fs.readFile(path.join(repoRoot, "frontend", "package-lock.json"), "utf8"));
const composerLock = JSON.parse(await fs.readFile(path.join(repoRoot, "api", "composer.lock"), "utf8"));

const npmPackages = [];
for (const [pkgPath, meta] of Object.entries(packageLock.packages || {})) {
  if (!pkgPath) continue;
  const name = pkgPath.replace(/^node_modules\//, "");
  const license =
    meta.license || (Array.isArray(meta.licenses) ? meta.licenses.map((x) => x.type).join(" OR ") : "MISSING");
  npmPackages.push({
    name,
    version: meta.version || "unknown",
    license,
    status: license === "MISSING" ? "unknown" : isAllowed(license) ? "allowed" : "review",
  });
}

const composerPackages = (composerLock.packages || []).map((pkg) => {
  const license = Array.isArray(pkg.license) && pkg.license.length > 0 ? pkg.license.join(" OR ") : "MISSING";
  return {
    name: pkg.name,
    version: pkg.version,
    license,
    status: license === "MISSING" ? "unknown" : isAllowed(license) ? "allowed" : "review",
  };
});

const summary = {
  npm: {
    total: npmPackages.length,
    unknown: npmPackages.filter((x) => x.status === "unknown").length,
    review: npmPackages.filter((x) => x.status === "review").length,
  },
  composer: {
    total: composerPackages.length,
    unknown: composerPackages.filter((x) => x.status === "unknown").length,
    review: composerPackages.filter((x) => x.status === "review").length,
  },
};

await fs.mkdir(outDir, { recursive: true });
await fs.writeFile(
  outFile,
  JSON.stringify(
    {
      generatedAt: new Date().toISOString(),
      policy: {
        mode: "report-only",
        note: "GPL compliance audit report; does not fail CI in current policy mode.",
      },
      summary,
      npmPackages,
      composerPackages,
    },
    null,
    2
  ) + "\n",
  "utf8"
);

console.log(`License audit report written to ${path.relative(repoRoot, outFile)}`);
console.log(
  `NPM review=${summary.npm.review}, unknown=${summary.npm.unknown}; Composer review=${summary.composer.review}, unknown=${summary.composer.unknown}`
);
