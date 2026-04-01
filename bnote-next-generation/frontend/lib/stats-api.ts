import { api } from "@/lib/api";

export interface StatsParticipant {
  firstName: string;
  surname: string;
  instrument: string;
  score: number;
  rank: number;
}

export interface StatsCriticalEvent {
  type: "rehearsal" | "concert";
  id: number;
  title: string;
  begin: string;
  approveUntil: string;
  invitedUsers: number;
  repliedUsers: number;
  pendingUsers: number;
  severity: "warning" | "critical";
}

export interface StatsDashboardData {
  overview: {
    criticalEvents: number;
    pendingResponses: number;
    participationRate: number;
    rehearsalParticipationRate: number;
    concertParticipationRate: number;
    responseCompletionRate: number;
    responsesTotal: number;
    invitationsTotal: number;
    rehearsalsTotal: number;
    concertsTotal: number;
  };
  eventsByMonth: Array<{
    month: string;
    rehearsals: number;
    concerts: number;
  }>;
  membersPerGroup: Array<{
    name: string;
    count: number;
  }>;
  participationTrend: Array<{
    month: string;
    rate: number;
    yes: number;
    total: number;
  }>;
  responseCompletionTrend: Array<{
    month: string;
    rate: number;
    replied: number;
    invited: number;
  }>;
  criticalEventsList: StatsCriticalEvent[];
  topParticipants: {
    rehearsals: StatsParticipant[];
    votes: StatsParticipant[];
  };
  meta: {
    months: number;
    generatedAt: string;
    scope: "year" | "all";
    year: number;
    availableYears: number[];
  };
}

export const statsApi = {
  getDashboard: (scope: "year" | "all", year: number) =>
    api.get<StatsDashboardData>("stats", "dashboard", { scope, year: String(year) }),
};

