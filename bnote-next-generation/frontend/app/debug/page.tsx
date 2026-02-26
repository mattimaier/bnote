/**
 * BNote Next Generation - Debug page
 * Test API endpoints and view entity/icon configuration.
 */

"use client";

import Link from "next/link";
import { useState, useCallback } from "react";
import { getApiUrl } from "@/lib/api";
import { getIcon } from "@/components/icons";
import { getPillStyle, getDotStyle } from "@/lib/entity-config";
import { PAGE_CONTENT_BASE_CLASS } from "@/lib/layout";
import entityConfigData from "@/config/entity-config.json";

const ENTITIES = (entityConfigData as { entities?: Record<string, { color: string; icon: string }> }).entities ?? {};

const API_PRESETS: { label: string; module: string; action: string; method?: "GET" | "POST"; params?: Record<string, string> }[] = [
  { label: "Session", module: "auth", action: "session" },
  { label: "Public config", module: "auth", action: "getPublicConfig", params: { debug: "1" } },
  { label: "Get modules", module: "auth", action: "getModules", params: { debug: "1" } },
  { label: "User lang", module: "auth", action: "getUserLang" },
  { label: "Dashboard", module: "dashboard", action: "list" },
  { label: "Translations (de)", module: "translations", action: "list", params: { lang: "de" } },
];

export default function DebugPage() {
  const [module, setModule] = useState("auth");
  const [action, setAction] = useState("session");
  const [method, setMethod] = useState<"GET" | "POST">("GET");
  const [paramsJson, setParamsJson] = useState("{}");
  const [response, setResponse] = useState<string>("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const callApi = useCallback(async () => {
    setError("");
    setResponse("");
    setLoading(true);
    try {
      const baseUrl = getApiUrl();
      const url = new URL(baseUrl.startsWith("http") ? baseUrl : baseUrl, typeof window !== "undefined" ? window.location.origin : "http://localhost");
      url.searchParams.set("module", module);
      url.searchParams.set("action", action);
      let params: Record<string, string> = {};
      try {
        params = JSON.parse(paramsJson || "{}");
      } catch {
        setError("Invalid JSON in params");
        setLoading(false);
        return;
      }
      Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, String(v)));

      const options: RequestInit = {
        method,
        credentials: "same-origin",
        headers: { "Content-Type": "application/json" },
      };
      if (method === "POST") {
        options.body = JSON.stringify({ action, ...params });
      }

      const res = await fetch(url.toString(), options);
      const text = await res.text();
      let formatted = text;
      try {
        const json = JSON.parse(text);
        formatted = JSON.stringify(json, null, 2);
      } catch {
        // keep raw text
      }
      setResponse(formatted);
      if (!res.ok) setError(`HTTP ${res.status}`);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Request failed");
      setResponse("");
    } finally {
      setLoading(false);
    }
  }, [module, action, method, paramsJson]);

  const applyPreset = useCallback(
    (p: (typeof API_PRESETS)[0]) => {
      setModule(p.module);
      setAction(p.action);
      setMethod(p.method ?? "GET");
      setParamsJson(JSON.stringify(p.params ?? {}, null, 2));
    },
    []
  );

  return (
    <div className="min-h-screen bg-zinc-100 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 p-6">
      <div className={`${PAGE_CONTENT_BASE_CLASS} space-y-8`}>
        <h1 className="text-2xl font-bold">Debug</h1>

        {/* Entity debug (all types) */}
        <section className="rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 p-4 shadow-sm">
          <h2 className="text-lg font-semibold mb-3">Entity debug views</h2>
          <p className="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
            View or edit entity pages for all types. Rehearsal and concert use mock data; others show a placeholder and link to the app.
          </p>
          <div className="flex flex-wrap gap-2">
            <Link
              href="/debug/entity"
              className="px-3 py-2 rounded-md text-sm bg-blue-600 text-white hover:bg-blue-700"
            >
              All entity types
            </Link>
            <Link
              href="/debug/entity/rehearsal"
              className="px-3 py-2 rounded-md text-sm bg-zinc-200 dark:bg-zinc-700 hover:bg-zinc-300 dark:hover:bg-zinc-600"
            >
              Rehearsal
            </Link>
            <Link
              href="/debug/entity/concert"
              className="px-3 py-2 rounded-md text-sm bg-zinc-200 dark:bg-zinc-700 hover:bg-zinc-300 dark:hover:bg-zinc-600"
            >
              Concert
            </Link>
          </div>
        </section>

        {/* API Tester */}
        <section className="rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 p-4 shadow-sm">
          <h2 className="text-lg font-semibold mb-3">API Tester</h2>
          <p className="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
            Base URL: <code className="bg-zinc-200 dark:bg-zinc-700 px-1 rounded">{getApiUrl()}</code>
          </p>
          <div className="flex flex-wrap gap-2 mb-3">
            {API_PRESETS.map((p) => (
              <button
                key={`${p.module}-${p.action}`}
                type="button"
                onClick={() => applyPreset(p)}
                className="px-3 py-1.5 rounded-md text-sm bg-zinc-200 dark:bg-zinc-700 hover:bg-zinc-300 dark:hover:bg-zinc-600"
              >
                {p.label}
              </button>
            ))}
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
            <div>
              <label className="block text-xs font-medium text-zinc-500 mb-1">Module</label>
              <input
                type="text"
                value={module}
                onChange={(e) => setModule(e.target.value)}
                className="w-full rounded border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-2 py-1.5 text-sm"
              />
            </div>
            <div>
              <label className="block text-xs font-medium text-zinc-500 mb-1">Action</label>
              <input
                type="text"
                value={action}
                onChange={(e) => setAction(e.target.value)}
                className="w-full rounded border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-2 py-1.5 text-sm"
              />
            </div>
          </div>
          <div className="flex gap-3 mb-3">
            <div className="flex items-center gap-2">
              <label className="text-sm">Method</label>
              <select
                value={method}
                onChange={(e) => setMethod(e.target.value as "GET" | "POST")}
                className="rounded border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-2 py-1.5 text-sm"
              >
                <option value="GET">GET</option>
                <option value="POST">POST</option>
              </select>
            </div>
          </div>
          <div className="mb-3">
            <label className="block text-xs font-medium text-zinc-500 mb-1">Params (JSON)</label>
            <textarea
              value={paramsJson}
              onChange={(e) => setParamsJson(e.target.value)}
              rows={3}
              className="w-full rounded border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-2 py-1.5 text-sm font-mono"
            />
          </div>
          <button
            type="button"
            onClick={callApi}
            disabled={loading}
            className="px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
          >
            {loading ? "Calling…" : "Call API"}
          </button>
          {error && <p className="mt-2 text-sm text-red-600 dark:text-red-400">{error}</p>}
          {response && (
            <pre className="mt-3 p-3 rounded border border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-900 text-xs overflow-auto max-h-96 font-mono whitespace-pre-wrap">
              {response}
            </pre>
          )}
        </section>

        {/* Icon / entity config */}
        <section className="rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 p-4 shadow-sm">
          <h2 className="text-lg font-semibold mb-3">Entity / icon configuration</h2>
          <p className="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
            From <code className="bg-zinc-200 dark:bg-zinc-700 px-1 rounded">config/entity-config.json</code>
          </p>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            {Object.entries(ENTITIES).map(([type, entry]) => {
              const Icon = getIcon(entry.icon);
              const pillStyle = getPillStyle(entry.color);
              const dotStyle = getDotStyle(entry.color);
              return (
                <div
                  key={type}
                  className="rounded-lg border border-zinc-200 dark:border-zinc-600 p-3 flex items-center gap-3"
                >
                  <div
                    className="w-10 h-10 rounded-full flex items-center justify-center shrink-0 text-white"
                    style={dotStyle}
                  >
                    <Icon className="w-5 h-5" />
                  </div>
                  <div className="min-w-0">
                    <div className="font-medium text-sm">{type}</div>
                    <div className="text-xs text-zinc-500 dark:text-zinc-400">
                      icon: <code>{entry.icon}</code>
                    </div>
                    <div
                      className="mt-1 rounded px-2 py-0.5 text-xs inline-block"
                      style={pillStyle}
                    >
                      {entry.color}
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        </section>
      </div>
    </div>
  );
}
