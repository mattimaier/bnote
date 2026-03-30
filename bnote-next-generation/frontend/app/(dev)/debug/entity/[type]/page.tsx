/**
 * BNote Next Generation - Debug entity index by type
 *
 * Copyright (C) 2026 BNote Contributors
 */

import Link from "next/link";
import { ALL_DEBUG_ENTITY_TYPES, getEntityTypeLabel, hasFullMockView, isDebugEntityType } from "@/lib/entities/debug/entity-types";
import { DebugPageShell, DebugSection } from "@/components/debug/DebugChrome";

interface PageProps {
  params: Promise<{ type: string }>;
}

const MOCK_IDS = ["1", "2"];

export async function generateStaticParams() {
  return ALL_DEBUG_ENTITY_TYPES.map((type) => ({ type }));
}

export default async function DebugEntityTypePage(props: PageProps) {
  const { type } = await props.params;
  if (!isDebugEntityType(type)) {
    return (
      <DebugPageShell
        breadcrumb={[
          { label: "Developer", href: "/developer/" },
          { label: "Debug", href: "/debug/" },
          { label: "Entity", href: "/debug/entity/" },
          { label: "Unknown" },
        ]}
        title="Unknown entity type"
        subtitle={`No debug route for “${type}”.`}
      >
        <DebugSection title="Next steps">
          <Link href="/debug/entity/" className="btn btn-soft btn-sm btn-primary" prefetch={false}>
            All entity types
          </Link>
        </DebugSection>
      </DebugPageShell>
    );
  }

  const label = getEntityTypeLabel(type);
  const fullMock = hasFullMockView(type);
  return (
    <DebugPageShell
      breadcrumb={[
        { label: "Developer", href: "/developer/" },
        { label: "Debug", href: "/debug/" },
        { label: "Entity", href: "/debug/entity/" },
        { label },
      ]}
      title={label}
      subtitle={
        fullMock
          ? "Open view or edit with mock data (no API). IDs are for display only; the same mock payload is used."
          : "Placeholder view/edit; use links below or open in the app."
      }
      headerActions={
        <Link href="/debug/entity/" className="btn btn-soft btn-sm" prefetch={false}>
          All types
        </Link>
      }
    >
      <DebugSection title="View">
        <div className="flex flex-wrap gap-2">
          {MOCK_IDS.map((id) => (
            <Link
              key={id}
              href={`/debug/entity/${type}/${id}/`}
              className="btn btn-soft btn-sm"
              prefetch={false}
            >
              View id={id}
            </Link>
          ))}
        </div>
      </DebugSection>
      <DebugSection title="Edit">
        <div className="flex flex-wrap gap-2">
          {MOCK_IDS.map((id) => (
            <Link
              key={id}
              href={`/debug/entity/${type}/${id}/edit/`}
              className="btn btn-soft btn-sm"
              prefetch={false}
            >
              Edit id={id}
            </Link>
          ))}
          {fullMock ? (
            <Link href={`/debug/entity/${type}/new/edit/`} className="btn btn-soft btn-sm btn-primary" prefetch={false}>
              Create (new)
            </Link>
          ) : null}
        </div>
      </DebugSection>
    </DebugPageShell>
  );
}
