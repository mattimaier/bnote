/**
 * BNote Next Generation - Entity view/edit (query-based: ?type=...&id=...&edit=1)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useRouter } from "next/navigation";
import { Suspense, useEffect } from "react";
import { getRedirectPath } from "@/lib/entities/paths";
import { useEntityParams } from "@/lib/entities/use-entity-params";
import { EventDetail } from "@/components/entities/event/EventDetail";
import { LocationDetail } from "@/components/entities/location/LocationDetail";
import { EquipmentDetail } from "@/components/entities/equipment/EquipmentDetail";
import { OutfitDetail } from "@/components/entities/outfit/OutfitDetail";
import { SongDetail } from "@/components/entities/song/SongDetail";
import { VoteDetail } from "@/components/entities/vote/VoteDetail";
import { ContactDetail } from "@/components/entities/contact/ContactDetail";
import { UserDetail } from "@/components/entities/user/UserDetail";
import { LocationEdit } from "@/components/entities/location/LocationEdit";
import { EquipmentEdit } from "@/components/entities/equipment/EquipmentEdit";
import { OutfitEdit } from "@/components/entities/outfit/OutfitEdit";
import { SongEdit } from "@/components/entities/song/SongEdit";
import { VoteEdit } from "@/components/entities/vote/VoteEdit";
import { ContactEdit } from "@/components/entities/contact/ContactEdit";
import { UserEdit } from "@/components/entities/user/UserEdit";

const ENTITY_TYPES_WITH_VIEW: Record<string, boolean> = {
  rehearsal: true,
  concert: true,
  location: true,
  equipment: true,
  outfit: true,
  song: true,
  vote: true,
  contact: true,
  user: true,
};

const ENTITY_TYPES_WITH_EDIT: Record<string, boolean> = {
  rehearsal: true,
  concert: true,
  location: true,
  equipment: true,
  outfit: true,
  song: true,
  contact: true,
  user: true,
  vote: true,
};

const Spinner = () => (
  <div className="flex items-center justify-center py-12">
    <div className="h-8 w-8 animate-spin rounded-full border-2 border-[var(--primary)] border-t-transparent" />
  </div>
);

function EntityContent() {
  const router = useRouter();
  const { type, id, edit } = useEntityParams();

  useEffect(() => {
    if (!type || !id) {
      router.replace("/dashboard");
      return;
    }
    const typeKey = type.toLowerCase();
    const hasView = ENTITY_TYPES_WITH_VIEW[typeKey];
    const hasEdit = ENTITY_TYPES_WITH_EDIT[typeKey];
    if (edit && !hasEdit) {
      router.replace(getRedirectPath(type, id));
      return;
    }
    if (!edit && !hasView) {
      router.replace(getRedirectPath(type, id));
      return;
    }
  }, [type, id, edit, router]);

  if (!type || !id) return <Spinner />;

  const typeKey = type.toLowerCase();
  const showEdit = edit && ENTITY_TYPES_WITH_EDIT[typeKey];
  const showView = !edit && ENTITY_TYPES_WITH_VIEW[typeKey];

  if (showEdit) {
    if (typeKey === "location") return <LocationEdit />;
    if (typeKey === "equipment") return <EquipmentEdit />;
    if (typeKey === "outfit") return <OutfitEdit />;
    if (typeKey === "song") return <SongEdit />;
    if (typeKey === "vote") return <VoteEdit />;
    if (typeKey === "contact") return <ContactEdit />;
    if (typeKey === "user") return <UserEdit />;
    return (
      <Suspense fallback={<Spinner />}>
        <EventDetail type={type} id={id} mode="edit" />
      </Suspense>
    );
  }

  if (showView) {
    if (typeKey === "location") return <LocationDetail />;
    if (typeKey === "equipment") return <EquipmentDetail />;
    if (typeKey === "outfit") return <OutfitDetail />;
    if (typeKey === "song") return <SongDetail />;
    if (typeKey === "vote") return <VoteDetail />;
    if (typeKey === "contact") return <ContactDetail />;
    if (typeKey === "user") return <UserDetail />;
    return (
      <Suspense fallback={<Spinner />}>
        <EventDetail type={type} id={id} mode="view" />
      </Suspense>
    );
  }

  return <Spinner />;
}

export default function EntityPage() {
  return (
    <Suspense fallback={<Spinner />}>
      <EntityContent />
    </Suspense>
  );
}
