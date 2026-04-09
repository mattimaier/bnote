/**
 * BNote Next Generation - Debug page
 * Test API endpoints and view entity/icon configuration.
 */

"use client";

import Link from "next/link";
import { useState, useCallback } from "react";
import { getApiUrl } from "@/lib/api";
import { TablerIconByName } from "@/components/icons";
import { getPillStyle, getDotStyle } from "@/lib/entity-config";
import entityConfigData from "@/config/entity-config.json";
import { DebugPageShell, DebugSection } from "@/components/debug/DebugChrome";

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

  const applyPreset = useCallback((p: (typeof API_PRESETS)[0]) => {
    setModule(p.module);
    setAction(p.action);
    setMethod(p.method ?? "GET");
    setParamsJson(JSON.stringify(p.params ?? {}, null, 2));
  }, []);

  return (
    <DebugPageShell
      breadcrumb={[
        { label: "Developer", href: "/developer/" },
        { label: "Debug" },
      ]}
      title="Debug"
      subtitle="API tester, entity shortcuts, and configuration reference. English-only tooling surface."
      headerActions={
        <Link href="/developer/" className="btn btn-soft btn-sm gap-1" prefetch={false}>
          <TablerIconByName name="terminal" className="h-4 w-4" />
          Developer hub
        </Link>
      }
    >
      <DebugSection
        title="Entity debug views"
        description="View or edit entity pages for all types. Rehearsal and concert use mock data; others show a placeholder and link to the app."
      >
        <div className="flex flex-wrap gap-2">
          <Link href="/debug/entity/" className="btn btn-soft btn-sm btn-primary" prefetch={false}>
            All entity types
          </Link>
          <Link href="/debug/entity/rehearsal/" className="btn btn-soft btn-sm" prefetch={false}>
            Rehearsal
          </Link>
          <Link href="/debug/entity/concert/" className="btn btn-soft btn-sm" prefetch={false}>
            Concert
          </Link>
          <Link href="/debug/icons/" className="btn btn-soft btn-sm" prefetch={false}>
            Icon previews
          </Link>
        </div>
      </DebugSection>

      <DebugSection
        title="API tester"
        description={
          <>
            Base URL:{" "}
            <code className="rounded-md bg-base-200 px-1.5 py-0.5 text-xs font-mono text-base-content/90">{getApiUrl()}</code>
          </>
        }
      >
        <div className="flex flex-wrap gap-2">
          {API_PRESETS.map((p) => (
            <button
              key={`${p.module}-${p.action}`}
              type="button"
              onClick={() => applyPreset(p)}
                className="btn btn-soft btn-xs sm:btn-sm"
            >
              {p.label}
            </button>
          ))}
        </div>
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <label className="form-control w-full">
            <span className="label-text text-xs font-medium text-base-content/70">Module</span>
            <input
              type="text"
              value={module}
              onChange={(e) => setModule(e.target.value)}
              className="input input-bordered input-sm w-full font-mono"
            />
          </label>
          <label className="form-control w-full">
            <span className="label-text text-xs font-medium text-base-content/70">Action</span>
            <input
              type="text"
              value={action}
              onChange={(e) => setAction(e.target.value)}
              className="input input-bordered input-sm w-full font-mono"
            />
          </label>
        </div>
        <label className="form-control w-full max-w-xs">
          <span className="label-text text-xs font-medium text-base-content/70">Method</span>
          <select
            value={method}
            onChange={(e) => setMethod(e.target.value as "GET" | "POST")}
            className="select select-bordered select-sm w-full font-mono"
          >
            <option value="GET">GET</option>
            <option value="POST">POST</option>
          </select>
        </label>
        <label className="form-control w-full">
          <span className="label-text text-xs font-medium text-base-content/70">Params (JSON)</span>
          <textarea
            value={paramsJson}
            onChange={(e) => setParamsJson(e.target.value)}
            rows={4}
            className="textarea textarea-bordered w-full font-mono text-sm"
          />
        </label>
        <button type="button" onClick={callApi} disabled={loading} className="btn btn-soft btn-sm btn-primary">
          {loading ? "Calling…" : "Call API"}
        </button>
        {error ? <p className="text-sm text-error">{error}</p> : null}
        {response ? (
          <pre className="max-h-96 overflow-auto rounded-box border border-base-300 bg-base-200/50 p-3 text-xs font-mono leading-relaxed whitespace-pre-wrap">
            {response}
          </pre>
        ) : null}
      </DebugSection>

      <DebugSection
        title="Entity / icon configuration"
        description="Definitions in config/entity-config.json."
      >
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {Object.entries(ENTITIES).map(([type, entry]) => {
            const pillStyle = getPillStyle(entry.color);
            const dotStyle = getDotStyle(entry.color);
            return (
              <div
                key={type}
                className="flex items-center gap-3 rounded-box border border-base-200 bg-base-200/30 p-3 transition-colors hover:bg-base-200/50"
              >
                <div
                  className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-primary-content"
                  style={dotStyle}
                >
                  <TablerIconByName name={entry.icon} className="h-5 w-5" />
                </div>
                <div className="min-w-0">
                  <div className="font-medium text-sm text-base-content">{type}</div>
                  <div className="text-xs text-base-content/60">
                    icon: <code className="font-mono">{entry.icon}</code>
                  </div>
                  <div className="mt-1 inline-block rounded px-2 py-0.5 text-xs font-mono" style={pillStyle}>
                    {entry.color}
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      </DebugSection>
    </DebugPageShell>
  );
}
