/**
 * BNote Next Generation - Mock entity data for debug pages
 * Shape matches API response for rehearsals/concerts so EventDetail works unchanged.
 *
 * Copyright (C) 2026 BNote Contributors
 */

export function getMockRehearsal(_id: string): Record<string, unknown> {
  const begin = "2026-03-15 14:00:00";
  const end = "2026-03-15 17:00:00";
  return {
    canEdit: true,
    canEditParticipation: true,
    begin,
    end,
    date: begin,
    event_begin: begin,
    approve_until: "2026-03-14 12:00:00",
    status: "planned",
    notes: "Mock rehearsal notes for debug.",
    title: "",
    location: { id: 1, name: "Proberaum 1", address: { street: "Musterstr. 1", city: "Berlin", zip: "10115" } },
    conductor: { id: 1, name: "Max Dirigent" },
    eventContacts: [
      { id: 10, name: "Anna Kontakt" },
      { id: 11, name: "Bernd Musiker" },
    ],
    participantsByInstrument: [
      {
        instrument: { id: 1, name: "Violin", category: { id: 1, name: "Strings" } },
        participants: [
          { id: 10, name: "Anna Kontakt", participate: 1 },
          { id: 11, name: "Bernd Musiker", participate: null },
        ],
        stats: { yes: 1, maybe: 0, no: 0, pending: 1 },
      },
    ],
    participationStats: { total: 2, yes: 1, maybe: 0, no: 0, pending: 1 },
    songsToPractice: [{ id: 1, title: "Symphony No. 1", notes: null }],
    groups: [{ id: 1, name: "Orchestra" }],
  };
}

export function getMockConcert(_id: string): Record<string, unknown> {
  const begin = "2026-04-20 19:00:00";
  const end = "2026-04-20 21:30:00";
  return {
    canEdit: true,
    canEditParticipation: true,
    title: "Spring Concert 2026",
    begin,
    end,
    date: begin,
    event_begin: begin,
    approve_until: "2026-04-18 18:00:00",
    status: "confirmed",
    notes: "Mock concert notes.",
    meetingtime: "2026-04-20 18:00:00",
    organizer: "Concert Team",
    location: { id: 2, name: "Town Hall", address: { street: "Rathausplatz 1", city: "Berlin", zip: "10178" } },
    contact: { id: 20, name: "Event Contact", phone: "+49 30 123456", email: "contact@example.com" },
    program: { id: 1, name: "Program A", notes: null },
    outfit: { id: 1, name: "Black" },
    equipment: [{ id: 1, name: "Microphones" }],
    accommodation: { id: 2, name: "Hotel Central" },
    payment: 500,
    conditions: "Standard terms.",
    eventContacts: [{ id: 10, name: "Anna Kontakt" }],
    participantsByInstrument: [
      {
        instrument: { id: 1, name: "Violin", category: { id: 1, name: "Strings" } },
        participants: [{ id: 10, name: "Anna Kontakt", participate: 1 }],
        stats: { yes: 1, maybe: 0, no: 0, pending: 0 },
      },
    ],
    participationStats: { total: 1, yes: 1, maybe: 0, no: 0, pending: 0 },
    groups: [{ id: 1, name: "Orchestra" }],
  };
}

export function getMockEventData(type: string, id: string): Record<string, unknown> | null {
  if (type === "rehearsal") return getMockRehearsal(id);
  if (type === "concert") return getMockConcert(id);
  return null;
}
