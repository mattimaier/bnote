/**
 * BNote Next Generation - Imprint module (inside app shell, main content area)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { AppPageHeader } from "@/components/AppPageHeader";
import { ImprintLegalContent } from "@/components/imprint/ImprintLegalContent";
import { PageContent } from "@/components/PageContent";

export default function ImprintModulePage() {
  return (
    <PageContent>
      <AppPageHeader moduleKey="imprint" title="Impressum" />
      <ImprintLegalContent />
    </PageContent>
  );
}
