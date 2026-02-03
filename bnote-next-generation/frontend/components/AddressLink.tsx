/**
 * BNote Next Generation - Address link with maps popover
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React, { useEffect, useId, useRef, useState } from "react";
import {
  getAddressInfo,
  normalizeAddressText,
  formatAddressPartsMultiline,
  type AddressParts,
} from "@/lib/address-utils";

interface AddressLinkProps {
  name?: string | null;
  value: AddressParts | string | null | undefined;
  t: (k: string) => string;
  className?: string;
  textClassName?: string;
  renderRawIfNoAddress?: boolean;
}

export function AddressLink({
  name,
  value,
  t,
  className,
  textClassName,
  renderRawIfNoAddress = false,
}: AddressLinkProps) {
  const info = getAddressInfo(value);
  const [open, setOpen] = useState(false);
  const ref = useRef<HTMLSpanElement | null>(null);
  const menuId = useId();

  useEffect(() => {
    if (!open) return;
    const handleClick = (event: MouseEvent) => {
      const target = event.target as Node;
      if (ref.current?.contains(target)) return;
      setOpen(false);
    };
    const handleKey = (event: KeyboardEvent) => {
      if (event.key === "Escape") setOpen(false);
    };
    document.addEventListener("mousedown", handleClick);
    document.addEventListener("keydown", handleKey);
    return () => {
      document.removeEventListener("mousedown", handleClick);
      document.removeEventListener("keydown", handleKey);
    };
  }, [open]);

  if (!info) {
    if (renderRawIfNoAddress && typeof value === "string" && value.trim()) {
      return <span className={textClassName}>{normalizeAddressText(value)}</span>;
    }
    return null;
  }

  const displayText =
    typeof value === "string"
      ? info.formatted.replace(/,\\s*/g, "\n")
      : formatAddressPartsMultiline(value);
  const nameText = name ? name.trim() : "";
  const combinedText = [nameText, displayText].filter(Boolean).join("\n");

  const googleHref = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(info.query)}`;
  const appleHref = `https://maps.apple.com/?q=${encodeURIComponent(info.query)}`;

  const openLink = (href: string) => {
    window.open(href, "_blank", "noopener,noreferrer");
    setOpen(false);
  };

  return (
    <span ref={ref} className={`relative inline-flex ${className ?? ""}`}>
      <button
        type="button"
        className={`inline-flex items-center gap-1 rounded-md hover:text-[var(--primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--primary)]/30 ${textClassName ?? ""}`}
        aria-haspopup="menu"
        aria-expanded={open}
        aria-controls={menuId}
        aria-label={t("js.address.openInMaps")}
        onClick={(event) => {
          event.preventDefault();
          event.stopPropagation();
          setOpen((prev) => !prev);
        }}
      >
        <span className="whitespace-pre-line text-left">{combinedText}</span>
      </button>

      {open && (
        <span
          id={menuId}
          role="menu"
          className="absolute left-0 top-full z-[60] mt-2 min-w-[220px] rounded-lg border px-2 py-2 text-sm shadow-lg"
          style={{ background: "var(--card)", borderColor: "var(--border)", color: "var(--card-foreground)" }}
          onClick={(event) => event.stopPropagation()}
        >
          <button
            type="button"
            role="menuitem"
            className="flex w-full items-center rounded-md px-2 py-1.5 text-left hover:bg-[var(--muted)]"
            onClick={() => openLink(googleHref)}
          >
            {t("js.address.openInGoogleMaps")}
          </button>
          <button
            type="button"
            role="menuitem"
            className="flex w-full items-center rounded-md px-2 py-1.5 text-left hover:bg-[var(--muted)]"
            onClick={() => openLink(appleHref)}
          >
            {t("js.address.openInAppleMaps")}
          </button>
        </span>
      )}
    </span>
  );
}
