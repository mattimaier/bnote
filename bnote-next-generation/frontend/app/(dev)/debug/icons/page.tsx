/**
 * BNote Next Generation - Icon debug previews
 *
 * Copyright (C) 2026 BNote Contributors
 */

 "use client";

import { useMemo } from "react";
import { BNoteLogo, type BNoteLogoPadding, type BNoteLogoSize } from "@/components/BNoteLogo";
import { DebugPageShell, DebugSection } from "@/components/debug/DebugChrome";
import { prefixPath } from "@/lib/path";

const ICON_SIZES = [16, 32, 64, 180, 512] as const;
const LOGO_VARIANTS: Array<{ size: BNoteLogoSize; padding: BNoteLogoPadding; forceLight?: boolean; forceDark?: boolean; label: string }> = [
  { size: "sm", padding: "default", label: "sm / default / theme-aware" },
  { size: "sm", padding: "tight", label: "sm / tight / theme-aware" },
  { size: "sm", padding: "default", forceLight: true, label: "sm / default / forceLight" },
  { size: "sm", padding: "default", forceDark: true, label: "sm / default / forceDark" },
  { size: "lg", padding: "default", label: "lg / default / theme-aware" },
  { size: "lg", padding: "tight", label: "lg / tight / theme-aware" },
  { size: "lg", padding: "default", forceLight: true, label: "lg / default / forceLight" },
  { size: "lg", padding: "default", forceDark: true, label: "lg / default / forceDark" },
];

const RASTER_PREVIEWS = [
  { key: "svg", src: prefixPath("/icon.svg"), label: "App icon SVG", path: "frontend/app/icon.svg" },
  { key: "png", src: prefixPath("/BNote_Logo_prebuilt.png"), label: "Generated PNG", path: "frontend/public/BNote_Logo_prebuilt.png" },
  { key: "ico", src: prefixPath("/favicon.ico"), label: "Generated favicon ICO", path: "frontend/app/favicon.ico" },
] as const;

const MAIL_ICON_PREVIEWS = [
  { key: "rehearsal", label: "Rehearsal", icon: "rehearsal.png", badge: "rehearsal-badge.png" },
  { key: "concert", label: "Concert", icon: "concert.png", badge: "concert-badge.png" },
  { key: "vote", label: "Vote", icon: "vote.png", badge: "vote-badge.png" },
  { key: "task", label: "Task", icon: "task.png", badge: "task-badge.png" },
  { key: "reservation", label: "Reservation", icon: "reservation.png", badge: "reservation-badge.png" },
] as const;

export default function DebugIconsPage() {
  const cacheBust = useMemo(() => Date.now().toString(), []);

  return (
    <DebugPageShell
      breadcrumb={[
        { label: "Developer", href: "/developer/" },
        { label: "Debug", href: "/debug/" },
        { label: "Icons" },
      ]}
      title="Icon previews"
      subtitle="Preview BNote icon variants and generated assets at common favicon/app icon sizes."
    >
      <DebugSection
        title="BNoteLogo variants"
        description="Matrix for size, padding, and palette behavior used in the app."
      >
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
          {LOGO_VARIANTS.map((variant) => (
            <div key={variant.label} className="rounded-box border border-base-300 bg-base-100 p-3">
              <div className="flex min-h-28 items-center justify-center rounded-box bg-base-200/40">
                <BNoteLogo
                  size={variant.size}
                  padding={variant.padding}
                  forceLight={variant.forceLight}
                  forceDark={variant.forceDark}
                />
              </div>
              <p className="mt-2 text-xs text-base-content/70">{variant.label}</p>
            </div>
          ))}
        </div>
      </DebugSection>

      <DebugSection
        title="Generated assets"
        description="Rendered outputs produced by the build icon generation step."
      >
        <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
          {RASTER_PREVIEWS.map((asset) => (
            <div key={asset.key} className="rounded-box border border-base-300 bg-base-100 p-3">
              <div className="flex min-h-28 items-center justify-center rounded-box bg-base-200/40">
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img src={`${asset.src}?v=${cacheBust}`} alt={asset.label} className="h-16 w-16 object-contain" />
              </div>
              <p className="mt-2 text-sm font-medium text-base-content">{asset.label}</p>
              <p className="text-xs text-base-content/70 font-mono">{asset.path}</p>
            </div>
          ))}
        </div>
      </DebugSection>

      <DebugSection
        title="Pixel size strip"
        description="Quick legibility checks at favicon/app-icon target sizes."
      >
        <div className="space-y-4">
          {RASTER_PREVIEWS.map((asset) => (
            <div key={`strip-${asset.key}`} className="space-y-2">
              <p className="text-sm font-medium text-base-content">{asset.label}</p>
              <div className="flex flex-wrap gap-3">
                {ICON_SIZES.map((px) => (
                  <div key={`${asset.key}-${px}`} className="rounded-box border border-base-300 bg-base-100 p-2 text-center">
                    <div className="flex h-20 w-20 items-center justify-center rounded bg-base-200/40">
                      {/* eslint-disable-next-line @next/next/no-img-element */}
                      <img
                        src={`${asset.src}?v=${cacheBust}`}
                        alt={`${asset.label} ${px}px`}
                        style={{ width: `${px}px`, height: `${px}px` }}
                        className="object-contain"
                      />
                    </div>
                    <p className="mt-1 text-xs text-base-content/70">{px}px</p>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>
      </DebugSection>

      <DebugSection
        title="Mail icon assets"
        description="Generated PNGs used in email templates (glyph + full badge variants)."
      >
        <div className="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
          {MAIL_ICON_PREVIEWS.map((asset) => (
            <div key={asset.key} className="rounded-box border border-base-300 bg-base-100 p-3">
              <p className="text-sm font-medium text-base-content">{asset.label}</p>
              <div className="mt-2 flex items-center gap-4 rounded-box bg-base-200/40 p-3">
                <div className="text-center">
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img
                    src={`${prefixPath(`/generated-mail-icons/${asset.icon}`)}?v=${cacheBust}`}
                    alt={`${asset.label} mail glyph`}
                    className="mx-auto h-[18px] w-[18px] object-contain"
                  />
                  <p className="mt-1 text-[11px] text-base-content/70">18px glyph</p>
                </div>
                <div className="text-center">
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img
                    src={`${prefixPath(`/generated-mail-icons/${asset.badge}`)}?v=${cacheBust}`}
                    alt={`${asset.label} mail badge`}
                    className="mx-auto h-10 w-10 object-contain"
                  />
                  <p className="mt-1 text-[11px] text-base-content/70">40px badge</p>
                </div>
              </div>
              <p className="mt-2 text-xs text-base-content/70 font-mono">frontend/public/generated-mail-icons/{asset.badge}</p>
            </div>
          ))}
        </div>
      </DebugSection>
    </DebugPageShell>
  );
}
