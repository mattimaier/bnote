/**
 * Build metadata for beta bug reports.
 * Values are compile-time env reads with safe fallbacks.
 */

export interface BugReportBuildInfo {
  version: string;
  buildId: string;
  commit: string;
  buildTime: string;
}

function envOrUnknown(value: string | undefined): string {
  const v = (value ?? "").trim();
  return v !== "" ? v : "unknown";
}

export function getBugReportBuildInfo(): BugReportBuildInfo {
  return {
    version: envOrUnknown(process.env.NEXT_PUBLIC_APP_VERSION),
    buildId: envOrUnknown(process.env.NEXT_PUBLIC_APP_BUILD_ID),
    commit: envOrUnknown(process.env.NEXT_PUBLIC_APP_COMMIT),
    buildTime: envOrUnknown(process.env.NEXT_PUBLIC_APP_BUILD_TIME),
  };
}
