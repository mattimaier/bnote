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
}

export const wrappedApi = {
  getYear: (year: number) => api.get<WrappedYearData>("wrapped", "year", { year: String(year) }),
};

