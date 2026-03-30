/**
 * BNote Next Generation - Debug entity view (mock or placeholder)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { use } from "react";
import Link from "next/link";
import { EventDetail } from "@/components/entities/event/EventDetail";
import { DebugBreadcrumb, DebugPageShell, DebugSection } from "@/components/debug/DebugChrome";
import { PageContent } from "@/components/PageContent";
import { getMockEventData } from "@/lib/entities/debug/mock-data";
import { getEntityTypeLabel } from "@/lib/entities/debug/entity-types";
import { getRedirectPath, isEventEntityType } from "@/lib/entities/paths";

interface Props {
  params: Promise<{ type: string; id: string }>;
}

function PlaceholderEntityView({ type, id }: { type: string; id: string }) {
  const appPath = getRedirectPath(type, id);
  const label = getEntityTypeLabel(type);
  return (
    <DebugPageShell
      breadcrumb={[
        { label: "Developer", href: "/developer/" },
        { label: "Debug", href: "/debug/" },
        { label: "Entity", href: "/debug/entity/" },
        { label: `${label} (placeholder)` },
      ]}
      title={`${label} · id ${id}`}
      subtitle="Full view/edit is only implemented for rehearsal and concert. This type links to the real app."
      headerActions={
        <Link href={`/debug/entity/${type}/`} className="btn btn-soft btn-sm" prefetch={false}>
          Back to {label}
        </Link>
      }
    >
      <DebugSection title="Open in app">
        <Link href={appPath} className="btn btn-soft btn-sm btn-primary" prefetch={false}>
          Open in app →
        </Link>
      </DebugSection>
    </DebugPageShell>
  );
}

export function DebugEntityView({ params }: Props) {
  const { type, id } = use(params);
  if (!isEventEntityType(type)) {
    return <PlaceholderEntityView type={type} id={id} />;
  }
  const mockData = getMockEventData(type, id);
  if (!mockData) {
    return (
      <DebugPageShell
        breadcrumb={[
          { label: "Developer", href: "/developer/" },
          { label: "Debug", href: "/debug/" },
          { label: "Entity", href: "/debug/entity/" },
          { label: "Missing mock" },
        ]}
        title="No mock data"
        subtitle={`${type} / ${id}`}
      >
        <DebugSection title="What happened">
          <p className="text-sm text-base-content/70">No mock payload for this combination.</p>
          <Link href="/debug/" className="btn btn-soft btn-sm btn-primary" prefetch={false}>
            Back to Debug
          </Link>
        </DebugSection>
      </DebugPageShell>
    );
  }
  const label = getEntityTypeLabel(type);
  return (
    <PageContent className="space-y-4">
      <DebugBreadcrumb
        items={[
          { label: "Developer", href: "/developer/" },
          { label: "Debug", href: "/debug/" },
          { label: "Entity", href: "/debug/entity/" },
          { label, href: `/debug/entity/${type}/` },
          { label: `View · ${id}` },
        ]}
      />
      <EventDetail type={type} id={id} mode="view" initialData={mockData} />
    </PageContent>
  );
}
