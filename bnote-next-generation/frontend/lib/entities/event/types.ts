/**
 * BNote Next Generation - Event entity types (rehearsal / concert)
 *
 * Copyright (C) 2026 BNote Contributors
 */

export interface LocationObj {
  id?: number;
  name?: string;
  address?: { street?: string; city?: string; zip?: string; state?: string; country?: string };
}

export interface ConductorObj {
  id?: number;
  name?: string;
}

export interface SongObj {
  id?: number;
  title?: string;
  notes?: string | null;
}

export interface ProgramObj {
  id?: number;
  name?: string;
  notes?: string | null;
}

export interface OutfitObj {
  id?: number;
  name?: string;
}

export interface EquipmentObj {
  id?: number;
  name?: string;
}

export interface GroupObj {
  id?: number;
  name?: string;
}

export interface AccommodationObj {
  id?: number;
  name?: string;
  address?: Record<string, unknown>;
}

export interface ContactObj {
  id?: number;
  name?: string;
  phone?: string;
  mobile?: string;
  email?: string;
}

export interface SimpleOption {
  id: number;
  name: string | null;
  subtitle?: string | null;
}

export interface SongOption {
  id: number;
  title: string;
}

export interface RehearsalMeta {
  locations: SimpleOption[];
  groups: SimpleOption[];
  songs: SongOption[];
  conductors: SimpleOption[];
  contacts: SimpleOption[];
  statusOptions: string[];
  groupMembers?: Record<string, number[]>;
}

export interface ConcertMeta {
  locations: SimpleOption[];
  groups: SimpleOption[];
  programs: SimpleOption[];
  outfits: SimpleOption[];
  equipment: SimpleOption[];
  contacts: SimpleOption[];
  statusOptions: string[];
  groupMembers?: Record<string, number[]>;
}

export interface EventContact {
  id: number;
  name?: string | null;
}

export interface EditableSong {
  id: number;
  title: string;
  notes: string;
}

export interface EditableParticipant {
  userId: number;
  contactId: number;
  name: string;
  instrument: string;
  participate: "yes" | "maybe" | "no" | "pending";
}

export interface EventDetailForm {
  title: string;
  begin: string;
  end: string;
  approveUntil: string;
  meetingtime: string;
  status: string;
  notes: string;
  organizer: string;
  payment: string;
  conditions: string;
  locationId: number;
  conductorId: number;
  contactId: number;
  programId: number;
  outfitId: number;
  accommodationId: number;
  groups: number[];
  equipment: number[];
  eventContacts: number[];
  manualContacts: number[];
  excludedContacts: number[];
  manualContactsInitialized: boolean;
  songs: EditableSong[];
  participants: EditableParticipant[];
}
