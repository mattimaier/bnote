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
      _totals: {},
      total: 0,
    };
  }
  const params = buildParams(q, filters, limit);
  return api.get<SearchResults>("search", "", params);
}

export async function getSearchYears(): Promise<number[]> {
  return api.get<number[]>("search", "years");
}
