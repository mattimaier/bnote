/**
 * BNote Next Generation - Data privacy module (inside app shell)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { AppPageHeader } from "@/components/AppPageHeader";
import { PrivacyLegalContent } from "@/components/privacy/PrivacyLegalContent";
import { PageContent } from "@/components/PageContent";

export default function PrivacyModulePage() {
  return (
    <PageContent>
      <AppPageHeader moduleKey="privacy" title="Datenschutz" />
      <PrivacyLegalContent />
    </PageContent>
  );
}
