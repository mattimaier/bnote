/**
 * Imprint · Privacy · Terms links (login / register shells).
 */

"use client";

import Link from "next/link";
import { useI18n } from "@/contexts/I18nContext";

export function LegalFooter() {
  const { t } = useI18n();
  return (
    <div className="mt-8 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 px-1 text-center text-xs text-base-content/60 sm:mt-10">
      <Link href="/legal/imprint/" className="transition-colors hover:text-base-content">
        {t("js.legal.imprint")}
      </Link>
      <span aria-hidden className="text-base-content/30">
        ·
      </span>
      <Link href="/legal/privacy/" className="transition-colors hover:text-base-content">
        {t("js.legal.privacy")}
      </Link>
      <span aria-hidden className="text-base-content/30">
        ·
      </span>
      <Link href="/legal/terms/" className="transition-colors hover:text-base-content">
        {t("js.legal.terms")}
      </Link>
    </div>
  );
}
