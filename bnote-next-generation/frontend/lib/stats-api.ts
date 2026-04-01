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
  status?: string;
  locationName?: string;
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
  responseBehavior?: {
    leadTimeHours: {
      medianHours: number;
      p90Hours: number;
      avgHours: number;
      sampleSize: number;
    };
    lateResponses: {
      late: number;
      total: number;
      rate: number;
    };
    noResponses: {
      pending: number;
      invited: number;
      rate: number;
    };
    funnel: {
      invited: number;
      responded: number;
      confirmed: number;
    };
    mixTrend: Array<{
      month: string;
      invited: number;
      yes: number;
      maybe: number;
      no: number;
      pending: number;
      pendingRate: number;
    }>;
    byType: {
      rehearsals: {
        invited: number;
        responded: number;
        pending: number;
        pendingRate: number;
        lateRate: number;
      };
      concerts: {
        invited: number;
        responded: number;
        pending: number;
        pendingRate: number;
        lateRate: number;
      };
    };
  };
  participationStability?: {
    variance: number;
    stdDev: number;
  };
  activeMembersTrend?: {
    series: Array<{
      month: string;
      rate: number;
      active: number;
      total: number;
    }>;
    overallRate: number;
    active: number;
    total: number;
  };
  responseConsistency?: {
    buckets: Array<{ label: string; count: number }>;
    totalUsers: number;
  };
  taskCompletionLatency?: {
    series: Array<{ month: string; medianHours: number; count: number }>;
    overallMedian: number;
    available: boolean;
  };
  voteParticipationTrend?: {
    series: Array<{ month: string; rate: number; votes: number; eligible: number }>;
    overallRate: number;
  };
  reminderEffectiveness?: {
    beforeCount: number;
    afterCount: number;
    upliftRate: number;
    escalations: number;
  };
  instrumentCoverageRisk?: {
    byInstrument: Array<{ name: string; shortfalls: number; events: number }>;
    totalEvents: number;
  };
  userRankings?: {
    positive: Record<string, Array<{ userId: number; name: string; surname: string; instrument: string; value: number }>>;
    negative: Record<string, Array<{ userId: number; name: string; surname: string; instrument: string; value: number }>>;
    thresholds: { minInvited: number; minReplied: number };
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
