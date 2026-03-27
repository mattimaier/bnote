/**
 * Shared helpers for rehearsal prefill logic.
 */

export function syncEndDate(startValue: string, endValue: string): string {
  if (!startValue) return endValue;
  const startNorm = startValue.replace(" ", "T");
  const [startDate, startTime] = startNorm.split("T");
  if (!startDate) return endValue;
  if (!endValue) return `${startDate}T${startTime ?? "00:00"}`;
  const endNorm = endValue.replace(" ", "T");
  const [, endTime] = endNorm.split("T");
  return `${startDate}T${endTime ?? "00:00"}`;
}

export function addMinutesToInputDateTime(startValue: string, minutes: number): string {
  if (!startValue) return "";
  const normalized = startValue.replace(" ", "T");
  const date = new Date(normalized);
  if (Number.isNaN(date.getTime())) return "";
  date.setMinutes(date.getMinutes() + minutes);
  const yyyy = date.getFullYear();
  const mm = String(date.getMonth() + 1).padStart(2, "0");
  const dd = String(date.getDate()).padStart(2, "0");
  const hh = String(date.getHours()).padStart(2, "0");
  const min = String(date.getMinutes()).padStart(2, "0");
  return `${yyyy}-${mm}-${dd}T${hh}:${min}`;
}

export function getGroupContacts(groupIds: number[], groupMembers?: Record<string, number[]>): Set<number> {
  const result = new Set<number>();
  if (!groupMembers) return result;
  groupIds.forEach((groupId) => {
    const ids = groupMembers[String(groupId)] ?? [];
    ids.forEach((id) => result.add(id));
  });
  return result;
}

export function deriveEventContacts(
  groupIds: number[],
  manualContacts: number[],
  excludedContacts: number[],
  groupMembers?: Record<string, number[]>
): { eventContacts: number[]; validExcludedContacts: number[] } {
  const groupContacts = getGroupContacts(groupIds, groupMembers);
  const manual = new Set(manualContacts);
  const validExcludedContacts = excludedContacts.filter((id) => groupContacts.has(id));
  const excluded = new Set(validExcludedContacts);
  const merged = new Set<number>();
  groupContacts.forEach((id) => {
    if (!excluded.has(id)) merged.add(id);
  });
  manual.forEach((id) => merged.add(id));
  return {
    eventContacts: Array.from(merged),
    validExcludedContacts,
  };
}
