/**
 * Password field with zxcvbn-ts strength meter + server rule hints (BNote regex).
 */

"use client";

import { useEffect, useMemo } from "react";
import { zxcvbn } from "@zxcvbn-ts/core";
import { useI18n } from "@/contexts/I18nContext";
import { ensureZxcvbnOptions } from "@/lib/zxcvbn-init";

export interface PasswordStrengthFieldProps {
  id: string;
  value: string;
  onChange: (v: string) => void;
  autoComplete?: string;
  placeholder?: string;
  userInputs?: string[];
  showMeter?: boolean;
}

export function PasswordStrengthField({
  id,
  value,
  onChange,
  autoComplete = "new-password",
  placeholder = "",
  userInputs = [],
  showMeter = true,
}: PasswordStrengthFieldProps) {
  const { t, lang } = useI18n();

  useEffect(() => {
    ensureZxcvbnOptions(lang);
  }, [lang]);

  const result = useMemo(() => {
    if (!value) return null;
    ensureZxcvbnOptions(lang);
    return zxcvbn(value, userInputs.filter(Boolean));
  }, [value, userInputs, lang]);

  const score = result?.score ?? 0;
  const barClass = score <= 1 ? "bg-error" : score === 2 ? "bg-warning" : score >= 3 ? "bg-success" : "bg-base-300";

  const idx = Math.min(4, Math.max(0, score));
  const label = t(`js.register.passwordScore${idx}`);

  const suggestions = result?.feedback?.suggestions ?? [];
  const warning = result?.feedback?.warning;

  return (
    <div className="space-y-2">
      <input
        id={id}
        type="password"
        autoComplete={autoComplete}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        className="input input-md w-full"
      />
      {showMeter && value.length > 0 && (
        <div className="space-y-1">
          <div className="flex h-1.5 w-full overflow-hidden rounded-full bg-base-300">
            <div
              className={`h-full transition-all duration-300 ${barClass}`}
              style={{ width: `${((score + 1) / 5) * 100}%` }}
            />
          </div>
          <p className="text-xs text-base-content/70">{label}</p>
          {warning ? <p className="text-xs text-warning">{warning}</p> : null}
          {suggestions.length > 0 ? (
            <ul className="list-inside list-disc text-xs text-base-content/60">
              {suggestions.map((s) => (
                <li key={s}>{s}</li>
              ))}
            </ul>
          ) : null}
        </div>
      )}
      <p className="text-xs text-base-content/60">{t("js.register.passwordRulesHint")}</p>
    </div>
  );
}
