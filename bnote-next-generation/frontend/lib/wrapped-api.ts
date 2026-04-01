import { api } from "@/lib/api";

export interface WrappedYearData {
  year: number;
  yearRange: { start: string; end: string };
  profile: {
    firstName: string;
    bandName: string;
  };
  personal: {
    responses: {
      total: number;
      yes: number;
      maybe: number;
      no: number;
      yesRate: number;
      deadlineGapHours: number;
    };
    events: {
      total: number;
      rehearsals: number;
      concerts: number;
    };
    avgLeadHours: number;
    topMonth: string;
    funFacts: {
      favoriteType: "rehearsal" | "concert";
      responseStyle: "committed" | "balanced" | "selective";
    };
    vibePersona: {
      id: "reliable_anchor" | "early_bird" | "all_in" | "stage_beast";
      score: number;
      variant: number;
      proof: {
        label: "response_completion" | "deadline_gap" | "yes_rate" | "events";
        value: number;
        unit: "percent" | "days" | "count";
        direction: "higher_better" | "lower_better";
      };
    };
  };
  band?: {
    events: {
      rehearsals: number;
      concerts: number;
      total: number;
    };
    yesRate: number;
    topResponders: Array<{
      firstName: string;
      score: number;
    }>;
  };
  achievements: {
    personalBadges: Array<{
      id: "attendance_commitment" | "response_speed" | "response_reliability" | "event_energy";
      level: "gold" | "silver" | "bronze";
      value: number;
      unit: "percent" | "hours" | "count" | "deadline_gap_hours";
    }>;
    bandLeaderboard: {
      minEvents: number;
      topAttendance: Array<{
        firstName: string;
        surname: string;
        eventCount: number;
        attendanceCount: number;
        attendanceRate: number;
      }>;
      lowestAttendance: Array<{
        firstName: string;
        surname: string;
        eventCount: number;
        attendanceCount: number;
        attendanceRate: number;
      }>;
    };
  };
}

export const wrappedApi = {
  canAccess: () => api.get<{ canAccess: boolean }>("wrapped", "canAccess"),
  getYears: () => api.get<{ years: number[]; startYear: number; endYear: number }>("wrapped", "years"),
  getYear: (year: number) => api.get<WrappedYearData>("wrapped", "year", { year: String(year) }),
};
