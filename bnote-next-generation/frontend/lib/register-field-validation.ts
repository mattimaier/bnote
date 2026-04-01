/**
 * Client-side checks aligned with `bnote-next-generation/api/nextgen_registration.php`
 * and BNote `Regex` (special-character set from BNote/src/data/regex.php).
 */

const RX_SPECIAL =
  "'`,àáâãăäāåæćčçèéêĕëēìíîĭïðłñòóôõöőøœšùúûüűýÿþÀÁÂÃĂÄĀÅÆĆČÇÈÉÊĔËĒÌÍÎĬÏÐŁÑÒÓÔÕÖŐØŒŠÙÚÛÜŰÝÞß";

/** Escapes a string for safe inclusion inside a RegExp character class (except final `-` placement). */
function ccChars(s: string): string {
  return [...s]
    .map((ch) => {
      if (ch === "\\" || ch === "]" || ch === "^") return `\\${ch}`;
      if (ch === "-") return "\\-";
      return ch;
    })
    .join("");
}

const _sc = ccChars(RX_SPECIAL);

/** Name / surname / nickname (nickname optional on server). */
const RX_NAME = new RegExp(
  `^[\\p{L}\\p{N}${_sc} \\.\\-,;:_+&#'/?()]{1,50}$`,
  "u",
);

const RX_STREET = new RegExp(
  `^[\\p{L}${_sc}0-9 \\.,\\-/()]{1,45}$`,
  "u",
);

const RX_CITY = new RegExp(
  `^[\\p{L}${_sc}0-9 \\.,\\-]{1,45}$`,
  "u",
);

/** Postal code: 4–7 chars, letters/digits/spaces only (matches server). */
const RX_ZIP = /^[\p{L}0-9\s]{4,7}$/u;

const PHONE_RX = /^[0-9+\-/() ]{1,29}$/;

/** Any characters allowed; length rule enforced (server-aligned). */
const RX_PASSWORD = /^[\s\S]{6,45}$/;

const RX_API_DATE = /^\d{4}-\d{2}-\d{2}$/;
const RX_API_EMAIL =
  /^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@([a-zA-Z0-9-]+\.)+([a-zA-Z]{2,4})$/;

function isRealDateYmd(ymd: string): boolean {
  if (!RX_API_DATE.test(ymd)) return false;
  const [y, m, d] = ymd.split("-").map((x) => parseInt(x, 10));
  const dt = new Date(Date.UTC(y, m - 1, d));
  return dt.getUTCFullYear() === y && dt.getUTCMonth() === m - 1 && dt.getUTCDate() === d;
}

export function registerNameValid(value: string): boolean {
  return RX_NAME.test(value.trim());
}

export function registerNicknameValid(value: string): boolean {
  const v = value.trim();
  if (!v) return true;
  return v.length <= 20 && RX_NAME.test(v);
}

export function registerStreetValid(value: string): boolean {
  return RX_STREET.test(value.trim());
}

export function registerCityValid(value: string): boolean {
  return RX_CITY.test(value.trim());
}

export function registerZipValid(value: string): boolean {
  return RX_ZIP.test(value.trim());
}

export function registerPhoneValid(value: string): boolean {
  const v = value.trim();
  if (!v) return true;
  return PHONE_RX.test(v);
}

export function registerPasswordValid(value: string): boolean {
  return RX_PASSWORD.test(value);
}

/** Empty is allowed (optional field); otherwise YYYY-MM-DD and real calendar date. */
export function registerBirthdayValid(value: string): boolean {
  const v = value.trim();
  if (!v) return true;
  return isRealDateYmd(v);
}

/** Same pattern as the registration API (stricter than generic email-validator). */
export function registerApiEmailValid(value: string): boolean {
  const v = value.trim();
  if (v.length > 45) return false;
  return RX_API_EMAIL.test(v);
}
