"use client";

export interface SearchFieldProps {
  value: string;
  onChange: (value: string) => void;
  placeholder: string;
  className?: string;
}

export function SearchField({
  value,
  onChange,
  placeholder,
  className = "",
}: SearchFieldProps) {
  return (
    <div className={`flex w-full items-center gap-3 rounded-lg bg-base-200 px-3 py-2 ${className}`.trim()}>
      <input
        type="search"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        className="w-full bg-transparent px-0 py-1 text-sm outline-none text-base-content"
        aria-label={placeholder}
      />
    </div>
  );
}
