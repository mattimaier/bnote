/**
 * BNote Next Generation - Imprint module (inside app shell, main content area)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { AppPageHeader } from "@/components/AppPageHeader";
import { ImprintLegalContent } from "@/components/imprint/ImprintLegalContent";
import { PageContent } from "@/components/PageContent";
import { getIcon } from "@/components/icons";
import { getColor } from "@/lib/entity-config";

export default function ImprintModulePage() {
  const Icon = getIcon("building");
  const iconColor = getColor("imprint");

  return (
    <PageContent>
      <AppPageHeader
        title={
          <span className="inline-flex items-center gap-2">
            <Icon className="h-7 w-7 shrink-0" style={iconColor ? { color: iconColor } : undefined} />
            Impressum
          </span>
        }
      />
      <ImprintLegalContent />
    </PageContent>
  );
}
