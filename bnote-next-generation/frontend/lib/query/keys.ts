export const queryKeys = {
  auth: {
    session: ["auth", "session"] as const,
    modules: ["auth", "modules"] as const,
    publicConfig: ["auth", "publicConfig"] as const,
  },
  dashboard: {
    home: ["dashboard", "home"] as const,
    bandOverview: (isAdmin: boolean) => ["dashboard", "bandOverview", isAdmin ? "admin" : "user"] as const,
  },
  lists: {
    rehearsals: ["list", "rehearsals"] as const,
    concerts: ["list", "concerts"] as const,
  },
  participation: {
    status: (eventType: string, eventId: number) => ["participation", "status", eventType, eventId] as const,
    batch: (keys: string[]) => ["participation", "batch", ...keys] as const,
  },
  entities: {
    taskDetail: (id: number) => ["entity", "task", "detail", id] as const,
    locationDetail: (id: number) => ["entity", "location", "detail", id] as const,
    locationEvents: (id: number) => ["entity", "location", "events", id] as const,
    eventDetail: (type: string, id: number) => ["entity", type, "detail", id] as const,
    eventMeta: (type: string) => ["entity", type, "meta"] as const,
  },
} as const;
