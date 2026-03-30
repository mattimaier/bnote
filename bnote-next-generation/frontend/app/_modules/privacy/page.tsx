/**
 * BNote Next Generation - Data privacy module (inside app shell)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { AppPageHeader } from "@/components/AppPageHeader";
import { PrivacyLegalContent } from "@/components/privacy/PrivacyLegalContent";
import { PageContent } from "@/components/PageContent";
import { getIcon } from "@/components/icons";
import { getColor } from "@/lib/entity-config";

export default function PrivacyModulePage() {
  const Icon = getIcon("shield-check");
  const iconColor = getColor("privacy");

  return (
    <PageContent>
      <AppPageHeader
        title={
          <span className="inline-flex items-center gap-2">
            <Icon className="h-7 w-7 shrink-0" style={iconColor ? { color: iconColor } : undefined} />
            Datenschutz
          </span>
        }
      />
      <PrivacyLegalContent />
    </PageContent>
  );
}
