export const QUERY_STALE_TIMES = {
  authSessionMs: 5 * 60 * 1000,
  authModulesMs: 5 * 60 * 1000,
  dashboardMs: 30 * 1000,
  listMs: 30 * 1000,
  participationMs: 30 * 1000,
  entityDetailMs: 20 * 1000,
} as const;
