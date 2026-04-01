import type { NextConfig } from "next";
import { execSync } from "node:child_process";
import { readFileSync } from "node:fs";
import { resolve } from "node:path";

/** Default base path for subfolder deployment. Set NEXT_PUBLIC_BASE_PATH="" for local dev at root. */
const basePath = (process.env.NEXT_PUBLIC_BASE_PATH ?? "/bnote-next-generation").replace(/\/$/, "");

function fallback(value: string | undefined, fallbackValue: string): string {
  const normalized = (value ?? "").trim();
  return normalized !== "" ? normalized : fallbackValue;
}

function readGitCommit(): string {
  try {
    return execSync("git rev-parse --short=12 HEAD", { encoding: "utf8" }).trim();
  } catch {
    return "unknown";
  }
}

function readPackageVersion(): string {
  try {
    const raw = readFileSync(resolve(process.cwd(), "package.json"), "utf8");
    const parsed = JSON.parse(raw) as { version?: string };
    return fallback(parsed.version, "unknown");
  } catch {
    return "unknown";
  }
}

const gitCommit = readGitCommit();
const buildMetadata = {
  version: fallback(process.env.NEXT_PUBLIC_APP_VERSION, readPackageVersion()),
  buildId: fallback(process.env.NEXT_PUBLIC_APP_BUILD_ID, gitCommit),
  commit: fallback(process.env.NEXT_PUBLIC_APP_COMMIT, gitCommit),
  buildTime: fallback(process.env.NEXT_PUBLIC_APP_BUILD_TIME, new Date().toISOString()),
};

const nextConfig: NextConfig = {
  output: "export",
  basePath: basePath || undefined,
  trailingSlash: true,
  env: {
    NEXT_PUBLIC_APP_VERSION: buildMetadata.version,
    NEXT_PUBLIC_APP_BUILD_ID: buildMetadata.buildId,
    NEXT_PUBLIC_APP_COMMIT: buildMetadata.commit,
    NEXT_PUBLIC_APP_BUILD_TIME: buildMetadata.buildTime,
  },
  // In dev only: proxy /api to the PHP backend so login works without CORS (same origin).
  // With output: "export", rewrites are not applied in production - set NEXT_PUBLIC_API_BASE to your API URL instead.
  ...(process.env.NODE_ENV === "development" && {
    async rewrites() {
      const base = process.env.NEXT_PUBLIC_API_BASE || "http://localhost:8888/Bnote/bnote-next-generation";
      const target = base.endsWith("/") ? base.slice(0, -1) : base;
      return [{ source: "/api/:path*", destination: `${target}/api/:path*` }];
    },
  }),
};

export default nextConfig;
