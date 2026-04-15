/**
 * BNote Next Generation - BNote logo with blue gradient background
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useEffect, useId, useState } from "react";
import bnoteIconTokens from "@/config/bnote-icon-tokens.json";

export type BNoteLogoSize = "sm" | "lg";

/** Inner padding: "default" | "tight" (less space between container and logo). */
export type BNoteLogoPadding = "default" | "tight";

const sizeClasses: Record<BNoteLogoSize, Record<BNoteLogoPadding, { container: string; logo: string }>> = {
  sm: {
    default: { container: "h-9 w-9", logo: "h-9 w-9" },
    tight: { container: "h-9 w-9", logo: "h-9 w-9" },
  },
  lg: {
    default: { container: "h-24 w-24", logo: "h-24 w-24" },
    tight: { container: "h-24 w-24", logo: "h-24 w-24" },
  },
};

function superellipsePoint(theta: number, radius: number, exponent: number): [number, number] {
  const c = Math.cos(theta);
  const s = Math.sin(theta);
  const cx = Math.sign(c) * Math.pow(Math.abs(c), 2 / exponent);
  const sy = Math.sign(s) * Math.pow(Math.abs(s), 2 / exponent);
  return [radius * cx, radius * sy];
}

function buildSquirclePath(size: number, inset: number, exponent: number, steps: number): string {
  const center = size / 2;
  const radius = center - inset;
  const points: [number, number][] = [];
  for (let i = 0; i < steps; i += 1) {
    const t = (Math.PI * 2 * i) / steps;
    const [x, y] = superellipsePoint(t, radius, exponent);
    points.push([center + x, center + y]);
  }
  const [x0, y0] = points[0];
  const segments = points.slice(1).map(([x, y]) => `L ${x.toFixed(3)} ${y.toFixed(3)}`);
  return [`M ${x0.toFixed(3)} ${y0.toFixed(3)}`, ...segments, "Z"].join(" ");
}

function mixHex(hexA: string, hexB: string, t: number): string {
  const a = hexA.replace("#", "");
  const b = hexB.replace("#", "");
  if (a.length !== 6 || b.length !== 6) return hexA;
  const ar = parseInt(a.slice(0, 2), 16);
  const ag = parseInt(a.slice(2, 4), 16);
  const ab = parseInt(a.slice(4, 6), 16);
  const br = parseInt(b.slice(0, 2), 16);
  const bg = parseInt(b.slice(2, 4), 16);
  const bb = parseInt(b.slice(4, 6), 16);
  const r = Math.round(ar + (br - ar) * t);
  const g = Math.round(ag + (bg - ag) * t);
  const bch = Math.round(ab + (bb - ab) * t);
  return `#${[r, g, bch].map((v) => v.toString(16).padStart(2, "0")).join("")}`;
}

type ExplicitPalette = { bgStart: string; bgEnd: string; border: string; glyph: string };
type DerivedPalette = {
  glyph: string;
  bgStartMixWhite: number;
  bgEndMixWhite: number;
  borderMixWhite: number;
};

function resolvePalette(p: ExplicitPalette | DerivedPalette): ExplicitPalette {
  if ("bgStart" in p) return p;
  return {
    glyph: p.glyph,
    bgStart: mixHex(p.glyph, "#ffffff", p.bgStartMixWhite),
    bgEnd: mixHex(p.glyph, "#ffffff", p.bgEndMixWhite),
    border: mixHex(p.glyph, "#ffffff", p.borderMixWhite),
  };
}

export interface BNoteLogoProps {
  size?: BNoteLogoSize;
  /** Inner padding: "tight" = less space between blue box and logo. */
  padding?: BNoteLogoPadding;
  className?: string;
  /** Force light palette regardless of current theme (used for share cards/images). */
  forceLight?: boolean;
  /** Force dark palette regardless of current theme (debug/preview helper). */
  forceDark?: boolean;
}

export function BNoteLogo({
  size = "sm",
  padding = "default",
  className = "",
  forceLight = false,
  forceDark = false,
}: BNoteLogoProps) {
  const [isDark, setIsDark] = useState(false);
  const gradientId = useId().replace(/:/g, "_");
  const { container, logo } = sizeClasses[size][padding];
  const tokens = bnoteIconTokens as {
    geometry: {
      canvasSize: number;
      squircleExponent: number;
      pathSteps: number;
      borderInset: number;
      borderWidth: number;
      glyphTranslate: number;
      glyphScale: number;
    };
    palette: {
      light: ExplicitPalette | DerivedPalette;
      dark: ExplicitPalette | DerivedPalette;
    };
  };
  const palette = resolvePalette(
    forceLight ? tokens.palette.light : forceDark || isDark ? tokens.palette.dark : tokens.palette.light
  );
  const squircleFillPath = buildSquirclePath(
    tokens.geometry.canvasSize,
    0,
    tokens.geometry.squircleExponent,
    tokens.geometry.pathSteps
  );
  const squircleBorderPath = buildSquirclePath(
    tokens.geometry.canvasSize,
    tokens.geometry.borderInset,
    tokens.geometry.squircleExponent,
    tokens.geometry.pathSteps
  );

  useEffect(() => {
    if (forceLight || forceDark) return;
    const root = document.documentElement;
    if (!root) return;

    const syncTheme = () => setIsDark(root.classList.contains("dark"));
    syncTheme();

    const observer = new MutationObserver(syncTheme);
    observer.observe(root, { attributes: true, attributeFilter: ["class"] });
    return () => observer.disconnect();
  }, [forceDark, forceLight]);

  return (
    <div className={`flex items-center justify-center ${container} ${className}`.trim()}>
      <svg
        viewBox={`0 0 ${tokens.geometry.canvasSize} ${tokens.geometry.canvasSize}`}
        className={`${logo} object-contain`}
        role="img"
        aria-label="BNote"
      >
        <defs>
          <linearGradient id={gradientId} x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stopColor={palette.bgStart} />
            <stop offset="1" stopColor={palette.bgEnd} />
          </linearGradient>
        </defs>
        <path d={squircleFillPath} fill={`url(#${gradientId})`} />
        <path
          d={squircleBorderPath}
          fill="none"
          stroke={palette.border}
          strokeWidth={tokens.geometry.borderWidth}
          strokeLinejoin="round"
        />
        <g
          transform={`translate(${tokens.geometry.glyphTranslate},${tokens.geometry.glyphTranslate}) scale(${tokens.geometry.glyphScale})`}
        >
          <path
            fill={palette.glyph}
            fillRule="evenodd"
            d="m517.5 59c19.8-8.4 42.6 0.8 51 20.6l198.7 468c8.3 19.8-0.9 42.6-20.7 51l-463.7 196.8c-19.8 8.4-42.6-0.8-51-20.5l-198.7-468.1c-8.4-19.7 0.8-42.6 20.6-51l12.7-5.3c19.8-8.4 42.6 0.8 51 20.5l6.4 14.2c6.3 14.9 23.5 21.9 38.5 15.6 14.9-6.4 1.6-0.7 16.5-7.1 15-6.3 22-23.5 15.6-38.5l-6-14.3c-8.4-19.7 0.8-42.6 20.6-51l156.4-66.3c19.7-8.4 42.5 0.8 50.9 20.6l6.4 14.1c6.3 15 23.6 21.9 38.5 15.6 15-6.4 1.6-0.7 16.6-7.1 14.9-6.3 21.9-23.5 15.6-38.5l-6.1-14.3c-8.3-19.7 0.9-42.5 20.6-51zm-375.8 382.3l115.6 274c8.4 19.8 31.2 29 51 20.6l375.9-159.5c19.8-8.4 29-31.3 20.6-51l-116.2-273.8c-8.4-19.7-31.2-28.9-51-20.6l-375.3 159.4c-19.8 8.4-29 31.2-20.6 50.9zm334 64.4c4.3-5.2 9.1-9.6 14.1-13.4l-43-119.1-92.6 55.5 57.8 160.5q1.2 3.1 1.6 6.2c4 17-2.1 39.2-17.6 57.6-22.3 26.4-55.4 35-74 19.3-18.6-15.6-15.6-49.7 6.6-76.1 4.6-5.4 9.6-10 14.8-13.9l-56.4-156.4c-5-13.7 0.4-28.7 12.1-36.4q1.4-1.2 3.1-2.2l143.2-85.7q0.9-0.6 1.9-1.1 2.5-1.5 5.4-2.5c16.2-5.9 34 2.5 39.9 18.6l64.5 178.9c9.6 17.5 4.5 44.9-14.1 67-22.3 26.3-55.4 35-74 19.3-18.5-15.7-15.6-49.8 6.7-76.1zm-375.4-373.4c15.8-6.7 34.1 0.7 40.8 16.5l42.4 99.9c6.7 15.8-0.7 34.1-16.5 40.8-15.8 6.7-34.1-0.7-40.8-16.5l-42.4-99.9c-6.7-15.8 0.7-34.1 16.5-40.8zm298.7-126.7c15.8-6.8 34 0.6 40.8 16.4l42.4 99.9c6.7 15.8-0.7 34.1-16.5 40.8-15.9 6.7-34.1-0.7-40.8-16.5l-42.4-99.9c-6.7-15.8 0.6-34 16.5-40.7zm-345.3 250.3l463.8-196.9"
          />
        </g>
      </svg>
    </div>
  );
}
