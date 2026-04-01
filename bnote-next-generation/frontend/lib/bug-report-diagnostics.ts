/**
 * Client-side diagnostics capture for beta bug reporting.
 */

import { getBugReportBuildInfo, type BugReportBuildInfo } from "@/lib/bug-report-build";

export interface BugReportNetworkEvent {
  timestamp: string;
  method: string;
  url: string;
  status?: number;
  durationMs?: number;
  ok?: boolean;
  error?: string;
}

export interface BugReportLogEvent {
  timestamp: string;
  level: "log" | "warn" | "error";
  message: string;
}

export interface BugReportClientContext {
  route: string;
  currentUrl: string;
  viewport: string;
  language: string;
  theme: string;
  userAgent: string;
  clientTime: string;
  build: BugReportBuildInfo;
}

const MAX_NETWORK_EVENTS = 20;
const MAX_LOG_EVENTS = 50;

const sensitiveNeedles = [
  "password",
  "authorization",
  "cookie",
  "set-cookie",
  "secret",
  "apikey",
  "api_key",
];

let initialized = false;
const networkEvents: BugReportNetworkEvent[] = [];
const logEvents: BugReportLogEvent[] = [];

function nowIso(): string {
  return new Date().toISOString();
}

function trimText(text: string, max = 1000): string {
  return text.length > max ? `${text.slice(0, max)}…` : text;
}

function containsSensitive(text: string): boolean {
  const lower = text.toLowerCase();
  return sensitiveNeedles.some((needle) => lower.includes(needle));
}

function sanitizeUrl(rawUrl: string): string {
  try {
    const u = new URL(rawUrl, typeof window !== "undefined" ? window.location.origin : "http://localhost");
    for (const [key, value] of u.searchParams.entries()) {
      if (containsSensitive(key) || containsSensitive(value)) {
        u.searchParams.set(key, "[REDACTED]");
      }
    }
    return trimText(u.toString(), 1200);
  } catch {
    return trimText(rawUrl, 1200);
  }
}

function pushBounded<T>(arr: T[], next: T, max: number) {
  arr.push(next);
  if (arr.length > max) {
    arr.splice(0, arr.length - max);
  }
}

function safeSerialize(value: unknown): string {
  if (typeof value === "string") {
    return containsSensitive(value) ? "[REDACTED]" : trimText(value);
  }
  if (value instanceof Error) {
    const message = value.message ?? "Error";
    return containsSensitive(message) ? "[REDACTED]" : trimText(message);
  }
  try {
    const json = JSON.stringify(value);
    if (json == null) return String(value);
    return containsSensitive(json) ? "[REDACTED]" : trimText(json);
  } catch {
    return trimText(String(value));
  }
}

function captureLog(level: "log" | "warn" | "error", args: unknown[]) {
  const message = args.map((a) => safeSerialize(a)).join(" ");
  pushBounded(logEvents, { timestamp: nowIso(), level, message }, MAX_LOG_EVENTS);
}

export function initBugReportDiagnostics() {
  if (initialized || typeof window === "undefined") return;
  initialized = true;

  const originalFetch = window.fetch.bind(window);
  window.fetch = async (input: RequestInfo | URL, init?: RequestInit): Promise<Response> => {
    const started = performance.now();
    const method = (init?.method ?? (input instanceof Request ? input.method : "GET")).toUpperCase();
    const rawUrl = typeof input === "string" ? input : input instanceof URL ? input.toString() : input.url;
    const url = sanitizeUrl(rawUrl);
    try {
      const response = await originalFetch(input, init);
      pushBounded(
        networkEvents,
        {
          timestamp: nowIso(),
          method,
          url,
          status: response.status,
          durationMs: Math.round(performance.now() - started),
          ok: response.ok,
        },
        MAX_NETWORK_EVENTS
      );
      return response;
    } catch (error) {
      pushBounded(
        networkEvents,
        {
          timestamp: nowIso(),
          method,
          url,
          durationMs: Math.round(performance.now() - started),
          ok: false,
          error: safeSerialize(error),
        },
        MAX_NETWORK_EVENTS
      );
      throw error;
    }
  };

  const originalLog = console.log.bind(console);
  const originalWarn = console.warn.bind(console);
  const originalError = console.error.bind(console);

  console.log = (...args: unknown[]) => {
    captureLog("log", args);
    originalLog(...args);
  };
  console.warn = (...args: unknown[]) => {
    captureLog("warn", args);
    originalWarn(...args);
  };
  console.error = (...args: unknown[]) => {
    captureLog("error", args);
    originalError(...args);
  };

  window.addEventListener("error", (event) => {
    captureLog("error", [event.message || "window.error", event.filename, event.lineno, event.colno]);
  });

  window.addEventListener("unhandledrejection", (event) => {
    captureLog("error", ["unhandledrejection", event.reason]);
  });
}

export function getBugReportNetworkEvents(): BugReportNetworkEvent[] {
  return networkEvents.slice();
}

export function getBugReportLogEvents(): BugReportLogEvent[] {
  return logEvents.slice();
}

export function getBugReportClientContext(): BugReportClientContext {
  const href = typeof window !== "undefined" ? window.location.href : "";
  const route = sanitizeUrl(href);
  const currentUrl = trimText(href, 1200);
  const viewport = typeof window !== "undefined" ? `${window.innerWidth}x${window.innerHeight}` : "unknown";
  const language = typeof navigator !== "undefined" ? navigator.language : "unknown";
  const userAgent = typeof navigator !== "undefined" ? navigator.userAgent : "unknown";
  const theme =
    typeof document !== "undefined"
      ? document.documentElement.getAttribute("data-theme") ?? "unknown"
      : "unknown";

  return {
    route,
    currentUrl,
    viewport,
    language,
    theme,
    userAgent,
    clientTime: nowIso(),
    build: getBugReportBuildInfo(),
  };
}
