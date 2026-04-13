import { api } from "./api";

export type ConfigurationParamType =
  | "boolean"
  | "integer"
  | "char"
  | "time"
  | "reference_group"
  | "reference_conductor";

export interface ConfigurationParamMeta {
  param: string;
  type: ConfigurationParamType;
  section: string;
  caption: string;
  used_in_nextgen: boolean;
}

export interface ConfigurationOption {
  id: number;
  name: string;
}

export interface ConfigurationResponse {
  parameters: ConfigurationParamMeta[];
  values: Record<string, unknown>;
  options: {
    groups: ConfigurationOption[];
    conductors: ConfigurationOption[];
  };
  derived?: {
    publicConcertsFeedUrl?: string;
    publicConcertsFeedTokenizedUrl?: string;
    // Backward compatibility during rollout.
    publicGigsFeedUrl?: string;
    publicGigsFeedTokenizedUrl?: string;
  };
}

export interface InstrumentAdminCategory {
  id: number;
  name: string;
}

export interface InstrumentAdminInstrument {
  id: number;
  name: string;
  category_id: number;
  category_name?: string;
  rank?: number;
}

export interface InstrumentAliasPoolConfig {
  id: string;
  name: string;
  instrument_ids: number[];
}

export interface SectionInstrumentTarget {
  instrument_id: number;
  required: number;
}

export interface InstrumentSectionConfig {
  id: string;
  name: string;
  instrument_ids: number[];
  rehearsal_min_total?: number;
  concert_min_total?: number;
  concert_instrument_targets?: SectionInstrumentTarget[];
}

export interface InstrumentAdminDataResponse {
  categories: InstrumentAdminCategory[];
  instruments: InstrumentAdminInstrument[];
  aliasPools: InstrumentAliasPoolConfig[];
  sections: InstrumentSectionConfig[];
}

export const configurationApi = {
  canAccess: () => api.get<{ canAccess: boolean }>("configuration", "canAccess"),
  getConfig: () => api.get<ConfigurationResponse>("configuration", "getConfig"),
  updateConfig: (values: Record<string, unknown>) =>
    api.post<ConfigurationResponse>("configuration", "updateConfig", { values }),
  regeneratePublicConcertsFeedToken: () =>
    api.post<ConfigurationResponse>("configuration", "regeneratePublicConcertsFeedToken", {}),
  getInstrumentAdminData: () =>
    api.get<InstrumentAdminDataResponse>("configuration", "getInstrumentAdminData"),
  createCategory: (name: string) =>
    api.post<{ success: boolean }>("configuration", "createCategory", { name }),
  updateCategory: (id: number, name: string) =>
    api.post<{ success: boolean }>("configuration", "updateCategory", { id, name }),
  deleteCategory: (id: number) =>
    api.post<{ success: boolean }>("configuration", "deleteCategory", { id }),
  createInstrument: (input: { name: string; category_id?: number; rank?: number }) =>
    api.post<{ success: boolean }>("configuration", "createInstrument", input as Record<string, unknown>),
  updateInstrument: (input: { id: number; name: string; category_id?: number; rank?: number }) =>
    api.post<{ success: boolean }>("configuration", "updateInstrument", input as Record<string, unknown>),
  deleteInstrument: (id: number) =>
    api.post<{ success: boolean }>("configuration", "deleteInstrument", { id }),
  applyBigBandPresetMerge: () =>
    api.post<{ success: boolean; sections?: InstrumentSectionConfig[] }>("configuration", "applyBigBandPresetMerge", {}),
  saveInstrumentAliasPools: (aliasPools: InstrumentAliasPoolConfig[]) =>
    api.post<{ success: boolean; aliasPools: InstrumentAliasPoolConfig[] }>("configuration", "saveInstrumentAliasPools", { aliasPools }),
  saveInstrumentSections: (sections: InstrumentSectionConfig[]) =>
    api.post<{ success: boolean; sections: InstrumentSectionConfig[] }>("configuration", "saveInstrumentSections", { sections }),
  seedInstrumentDefaults: () =>
    api.post<{ success: boolean }>("configuration", "seedInstrumentDefaults", {}),
};
