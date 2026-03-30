/**
 * After `next build`, remove /debug and /developer from static export when
 * NEXT_PUBLIC_ENABLE_DEVELOPER_TOOLS is not set to 1 (production bundle).
 */
import { existsSync, rmSync } from "node:fs";
import { join } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = fileURLToPath(new URL(".", import.meta.url));
const frontendRoot = join(__dirname, "..");
const outDir = join(frontendRoot, "out");

const devTools = process.env.NEXT_PUBLIC_ENABLE_DEVELOPER_TOOLS === "1";

if (devTools) {
  console.log("prune-dev-artifacts: NEXT_PUBLIC_ENABLE_DEVELOPER_TOOLS=1 — keeping /debug and /developer in out/");
  process.exit(0);
}

const basePath = (process.env.NEXT_PUBLIC_BASE_PATH ?? "/bnote-next-generation").replace(/\/$/, "");
const prefix = basePath ? basePath.split("/").filter(Boolean) : [];

function removeRel(...segments) {
  const target = join(outDir, ...segments);
  if (existsSync(target)) {
    rmSync(target, { recursive: true, force: true });
    console.log("prune-dev-artifacts: removed", target);
  }
}

// Next may emit routes at out/debug or out/<basePath>/debug depending on config
removeRel("debug");
removeRel("developer");
if (prefix.length) {
  removeRel(...prefix, "debug");
  removeRel(...prefix, "developer");
}

console.log("prune-dev-artifacts: done (developer tools disabled for this build)");
