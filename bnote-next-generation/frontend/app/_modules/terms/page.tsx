/**
 * BNote Next Generation - Terms module (inside app shell)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { AppPageHeader } from "@/components/AppPageHeader";
import { PageContent } from "@/components/PageContent";
import { TermsLegalContent } from "@/components/legal/TermsLegalContent";
import { useI18n } from "@/contexts/I18nContext";

export default function TermsModulePage() {
  const { t } = useI18n();

  return (
    <PageContent>
      <AppPageHeader
        moduleKey="terms"
        iconName="file-text"
        title={t("js.legal.terms") !== "js.legal.terms" ? t("js.legal.terms") : "Terms of use"}
      />
      <TermsLegalContent />
    </PageContent>
  );
}
