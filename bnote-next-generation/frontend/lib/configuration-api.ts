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
}

export const configurationApi = {
  canAccess: () => api.get<{ canAccess: boolean }>("configuration", "canAccess"),
  getConfig: () => api.get<ConfigurationResponse>("configuration", "getConfig"),
  updateConfig: (values: Record<string, unknown>) =>
    api.post<ConfigurationResponse>("configuration", "updateConfig", { values }),
};

