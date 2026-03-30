/**
 * BNote Next Generation - Public data privacy page (login footer; no app shell)
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { BNoteLogo } from "@/components/BNoteLogo";
import { PrivacyLegalContent } from "@/components/privacy/PrivacyLegalContent";

export default function LegalPrivacyPage() {
  return (
    <main className="min-h-screen bg-base-100 px-4 py-8 sm:px-6 sm:py-10">
      <div className="mx-auto w-full max-w-4xl">
        <div className="mb-4 flex justify-center">
          <BNoteLogo size="lg" padding="tight" />
        </div>
        <h1 className="mb-6 text-3xl font-bold text-base-content">Datenschutz</h1>
        <PrivacyLegalContent />
      </div>
    </main>
  );
}
