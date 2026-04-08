/**
 * Compact legal footer links with optional icon style and route mode.
 */

"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useI18n } from "@/contexts/I18nContext";
import { getIcon } from "@/components/icons";
import { isImprintNavActive, isPrivacyNavActive, isTermsNavActive } from "@/lib/sidebar-active";

export interface LegalFooterProps {
  routeMode?: "auth" | "app";
  iconTone?: "muted";
  className?: string;
}

interface LegalLinkItem {
  key: "imprint" | "privacy" | "terms";
  i18nKey: "js.legal.imprint" | "js.legal.privacy" | "js.legal.terms";
  href: string;
  icon: string;
  active: (pathname: string | null | undefined) => boolean;
}

const AUTH_LINKS: LegalLinkItem[] = [
  {
    key: "imprint",
    i18nKey: "js.legal.imprint",
    href: "/legal/imprint/",
    icon: "building",
    active: isImprintNavActive,
  },
  {
    key: "privacy",
    i18nKey: "js.legal.privacy",
    href: "/legal/privacy/",
    icon: "shield-check",
    active: isPrivacyNavActive,
  },
  {
    key: "terms",
    i18nKey: "js.legal.terms",
    href: "/legal/terms/",
    icon: "file-text",
    active: isTermsNavActive,
  },
];

const APP_LINKS: LegalLinkItem[] = [
  {
    key: "imprint",
    i18nKey: "js.legal.imprint",
    href: "/imprint/",
    icon: "building",
    active: isImprintNavActive,
  },
  {
    key: "privacy",
    i18nKey: "js.legal.privacy",
    href: "/privacy/",
    icon: "shield-check",
    active: isPrivacyNavActive,
  },
  {
    key: "terms",
    i18nKey: "js.legal.terms",
    href: "/terms/",
    icon: "file-text",
    active: isTermsNavActive,
  },
];

export function LegalFooter({ routeMode = "auth", iconTone = "muted", className = "" }: LegalFooterProps) {
  const pathname = usePathname();
  const { t } = useI18n();
  const links = routeMode === "app" ? APP_LINKS : AUTH_LINKS;
  const spacingClass = routeMode === "app" ? "" : "mt-10 pb-6 sm:mt-12 sm:pb-8";

  return (
    <div
      className={`flex flex-wrap items-center justify-center gap-x-3 gap-y-1.5 px-1 text-center text-xs text-base-content/65 ${spacingClass} ${className}`.trim()}
    >
      {links.map((item) => {
        const Icon = getIcon(item.icon);
        const isActive = item.active(pathname);
        return (
          <Link
            key={item.key}
            href={item.href}
            className={`inline-flex items-center gap-1.5 rounded px-1 py-0.5 transition-colors ${
              isActive ? "text-primary" : "hover:text-base-content"
            }`}
          >
            <span
              className={`inline-flex items-center justify-center ${isActive ? "text-current" : ""}`}
              style={!isActive && iconTone === "muted" ? { color: "var(--muted-foreground)" } : undefined}
              aria-hidden
            >
              <Icon className="h-3.5 w-3.5" />
            </span>
            <span>{t(item.i18nKey)}</span>
          </Link>
        );
      })}
    </div>
  );
}
