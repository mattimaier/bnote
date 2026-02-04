/**
 * BNote Next Generation - Search API helpers
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { api } from "./api";

export interface SearchFilters {
  date_year?: number;
  date_month?: number;
  module_type?: string;
}

export interface SearchResults {
  rehearsals: SearchEventItem[];
  concerts: SearchEventItem[];
  users: SearchListItem[];
  contacts: SearchListItem[];
  tasks: SearchListItem[];
  repertoire: SearchListItem[];
  locations: SearchListItem[];
  equipment: SearchListItem[];
  outfits: SearchListItem[];
  songs: SearchListItem[];
  votes: SearchListItem[];
  _totals?: Record<string, number>;
  total?: number;
}

export interface SearchEventItem {
  id?: number;
  oid?: number;
  otype?: string;
  title?: string;
  begin?: string;
  eventBegin?: string;
  dueDate?: string;
  location?: string | { name?: string };
  locationData?: { name?: string };
  locationName?: string;
}

export interface SearchListItem {
  id: number;
  name?: string;
  title?: string;
  email?: string;
  phone?: string;
  mobile?: string;
  instrument?: string;
  dueAt?: string;
  assignee?: string;
  composer?: string;
  genre?: string;
  street?: string;
  city?: string;
}

const EVENT_KEYS: (keyof SearchResults)[] = ["rehearsals", "concerts"];
const LIST_KEYS: (keyof SearchResults)[] = [
  "users",
  "contacts",
  "tasks",
  "repertoire",
  "locations",
  "equipment",
  "outfits",
  "songs",
  "votes",
];

function buildParams(q: string, filters: SearchFilters, limit: number): Record<string, string> {
  const params: Record<string, string> = {
    q: q.trim(),
    limit: String(limit),
  };
  if (filters.date_year) params["filter[date_year]"] = String(filters.date_year);
  if (filters.date_month) params["filter[date_month]"] = String(filters.date_month);
  if (filters.module_type) params["filter[module_type]"] = filters.module_type;
  return params;
}

function isValidEventItem(item: SearchEventItem): boolean {
  const id = item.oid ?? item.id;
  return typeof id === "number" && Number.isFinite(id) && id > 0;
}

function isValidListItem(item: SearchListItem): boolean {
  return typeof item.id === "number" && Number.isFinite(item.id) && item.id > 0;
}

function sanitizeSearchResults(results: SearchResults): SearchResults {
  const cleaned: SearchResults = {
    ...results,
    rehearsals: Array.isArray(results.rehearsals) ? results.rehearsals.filter(isValidEventItem) : [],
    concerts: Array.isArray(results.concerts) ? results.concerts.filter(isValidEventItem) : [],
    users: Array.isArray(results.users) ? results.users.filter(isValidListItem) : [],
    contacts: Array.isArray(results.contacts) ? results.contacts.filter(isValidListItem) : [],
    tasks: Array.isArray(results.tasks) ? results.tasks.filter(isValidListItem) : [],
    repertoire: Array.isArray(results.repertoire) ? results.repertoire.filter(isValidListItem) : [],
    locations: Array.isArray(results.locations) ? results.locations.filter(isValidListItem) : [],
    equipment: Array.isArray(results.equipment) ? results.equipment.filter(isValidListItem) : [],
    outfits: Array.isArray(results.outfits) ? results.outfits.filter(isValidListItem) : [],
    songs: Array.isArray(results.songs) ? results.songs.filter(isValidListItem) : [],
    votes: Array.isArray(results.votes) ? results.votes.filter(isValidListItem) : [],
  };

  if (results._totals) {
    const totals: Record<string, number> = { ...results._totals };
    for (const key of EVENT_KEYS) {
      const raw = Array.isArray(results[key]) ? (results[key] as SearchEventItem[]).length : 0;
      const filtered = Array.isArray(cleaned[key]) ? (cleaned[key] as SearchEventItem[]).length : 0;
      const removed = Math.max(0, raw - filtered);
      if (removed > 0) totals[key] = Math.max(0, (totals[key] ?? 0) - removed);
    }
    for (const key of LIST_KEYS) {
      const raw = Array.isArray(results[key]) ? (results[key] as SearchListItem[]).length : 0;
      const filtered = Array.isArray(cleaned[key]) ? (cleaned[key] as SearchListItem[]).length : 0;
      const removed = Math.max(0, raw - filtered);
      if (removed > 0) totals[key] = Math.max(0, (totals[key] ?? 0) - removed);
    }
    cleaned._totals = totals;
    if (typeof results.total === "number") {
      const rawTotal = Object.values(results._totals).reduce((sum, v) => sum + (typeof v === "number" ? v : 0), 0);
      const filteredTotal = Object.values(totals).reduce((sum, v) => sum + (typeof v === "number" ? v : 0), 0);
      const removed = Math.max(0, rawTotal - filteredTotal);
      cleaned.total = Math.max(0, results.total - removed);
    }
  }

  return cleaned;
}

export async function performSearch(
  q: string,
  filters: SearchFilters = {},
  limit = 50
): Promise<SearchResults> {
  if (q.trim().length < 2) {
    return {
      rehearsals: [],
      concerts: [],
      users: [],
      contacts: [],
      tasks: [],
      repertoire: [],
      locations: [],
      equipment: [],
      outfits: [],
      songs: [],
      votes: [],
      _totals: {},
      total: 0,
    };
  }
  const params = buildParams(q, filters, limit);
  const results = await api.get<SearchResults>("search", "", params);
  return sanitizeSearchResults(results);
}

export async function getSearchYears(): Promise<number[]> {
  return api.get<number[]>("search", "years");
}
