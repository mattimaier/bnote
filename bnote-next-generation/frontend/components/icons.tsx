/**
 * BNote Next Generation - Icon mapping (Tabler via Iconify)
 * Icons for entity config and UI; names align with entity-config.json.
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React from "react";

/** Tabler icon name to full Iconify Tailwind class */
const TABLER_MAP: Record<string, string> = {
  "layout-dashboard": "icon-[tabler--layout-dashboard]",
  dashboard: "icon-[tabler--layout-dashboard]",
  "chart-bar": "icon-[tabler--chart-bar]",
  users: "icon-[tabler--users]",
  "user-plus": "icon-[tabler--user-plus]",
  user: "icon-[tabler--user]",
  "user-cog": "icon-[tabler--user-cog]",
  "calendar-days": "icon-[tabler--calendar-event]",
  "message-square": "icon-[tabler--message-circle]",
  music: "icon-[tabler--music]",
  trumpet: "icon-[tabler--music]",
  mic: "icon-[tabler--microphone]",
  "mic-vocal": "icon-[tabler--microphone]",
  "file-music": "icon-[tabler--file-music]",
  calendar: "icon-[tabler--calendar]",
  "map-pin": "icon-[tabler--map-pin]",
  "chevron-left": "icon-[tabler--chevron-left]",
  search: "icon-[tabler--search]",
  "user-circle": "icon-[tabler--user-circle]",
  "check-square": "icon-[tabler--checkbox]",
  package: "icon-[tabler--package]",
  map: "icon-[tabler--map]",
  plus: "icon-[tabler--plus]",
  x: "icon-[tabler--x]",
  "arrow-left": "icon-[tabler--arrow-left]",
  sun: "icon-[tabler--sun]",
  moon: "icon-[tabler--moon]",
  menu: "icon-[tabler--menu]",
  pencil: "icon-[tabler--pencil]",
  trash2: "icon-[tabler--trash]",
  key: "icon-[tabler--key]",
  "chevron-right": "icon-[tabler--chevron-right]",
  "chevron-down": "icon-[tabler--chevron-down]",
  "more-vertical": "icon-[tabler--dots-vertical]",
  "file-text": "icon-[tabler--file-text]",
  "file-image": "icon-[tabler--photo]",
  "file-audio": "icon-[tabler--file-music]",
  "file-video": "icon-[tabler--video]",
  "file-code": "icon-[tabler--code]",
  "file-archive": "icon-[tabler--archive]",
  folder: "icon-[tabler--folder]",
  "folder-open": "icon-[tabler--folder-open]",
  share: "icon-[tabler--share]",
  "share-2": "icon-[tabler--share]",
  printer: "icon-[tabler--printer]",
  "shield-alert": "icon-[tabler--shield-exclamation]",
  "shield-check": "icon-[tabler--shield-check]",
  mail: "icon-[tabler--mail]",
  send: "icon-[tabler--send]",
  phone: "icon-[tabler--phone]",
  tag: "icon-[tabler--tag]",
  bell: "icon-[tabler--bell]",
  shirt: "icon-[tabler--shirt]",
  vote: "icon-[tabler--circle-dot]",
  "layout-list": "icon-[tabler--list]",
  "arrow-up": "icon-[tabler--arrow-up]",
  "arrow-down": "icon-[tabler--arrow-down]",
  "arrow-up-down": "icon-[tabler--arrows-sort]",
  "check-circle": "icon-[tabler--circle-check]",
  "alert-circle": "icon-[tabler--alert-circle]",
  "loader-2": "icon-[tabler--loader-2]",
  "log-out": "icon-[tabler--logout]",
  "folder-plus": "icon-[tabler--folder-plus]",
  download: "icon-[tabler--download]",
  upload: "icon-[tabler--upload]",
  check: "icon-[tabler--check]",
  "help-circle": "icon-[tabler--help-circle]",
  clock: "icon-[tabler--clock]",
  save: "icon-[tabler--device-floppy]",
  info: "icon-[tabler--info-circle]",
  newspaper: "icon-[tabler--news]",
  cake: "icon-[tabler--cake]",
  confetti: "icon-[tabler--confetti]",
  "laurel-wreath-1": "icon-[tabler--rosette-number-1]",
  "laurel-wreath-2": "icon-[tabler--rosette-number-2]",
  "laurel-wreath-3": "icon-[tabler--rosette-number-3]",
  "calendar-check": "icon-[tabler--calendar-check]",
  "grip-vertical": "icon-[tabler--grip-vertical]",
  settings: "icon-[tabler--settings]",
  "alert-triangle": "icon-[tabler--alert-triangle]",
  building: "icon-[tabler--building]",
  terminal: "icon-[tabler--terminal-2]",
  "external-link": "icon-[tabler--external-link]",
};

export interface IconProps {
  className?: string;
  style?: React.CSSProperties;
  "aria-hidden"?: boolean;
}

function Icon({ name, className = "h-5 w-5", ...props }: IconProps & { name: string }) {
  const iconClass = TABLER_MAP[name?.toLowerCase()] ?? TABLER_MAP["layout-dashboard"];
  return <span className={`${iconClass} ${className}`} {...props} />;
}

/** Renders a Tabler icon by name without creating a new component type each render (eslint static-components). */
export function TablerIconByName({ name, ...props }: IconProps & { name: string }) {
  const key = String(name || "").toLowerCase().trim();
  const resolved = Object.keys(TABLER_MAP).find((k) => k === key) ?? "layout-dashboard";
  return <Icon name={resolved} {...props} />;
}

/** Returns a React component that renders the Tabler icon for the given name */
export function getIcon(name: string): React.ComponentType<IconProps> {
  const key = String(name || "").toLowerCase().trim();
  const iconName = Object.keys(TABLER_MAP).find((k) => k === key) ?? "layout-dashboard";
  const IconComponent = (props: IconProps) => <Icon name={iconName} {...props} />;
  IconComponent.displayName = `Icon(${iconName})`;
  return IconComponent;
}

/** Pre-built icon components for direct use */
export const Users = (p: IconProps) => <Icon name="users" {...p} />;
export const User = (p: IconProps) => <Icon name="user" {...p} />;
export const CalendarDays = (p: IconProps) => <Icon name="calendar-days" {...p} />;
export const Calendar = (p: IconProps) => <Icon name="calendar" {...p} />;
export const MapPin = (p: IconProps) => <Icon name="map-pin" {...p} />;
export const Search = (p: IconProps) => <Icon name="search" {...p} />;
export const Map = (p: IconProps) => <Icon name="map" {...p} />;
export const X = (p: IconProps) => <Icon name="x" {...p} />;
export const Menu = (p: IconProps) => <Icon name="menu" {...p} />;
export const Pencil = (p: IconProps) => <Icon name="pencil" {...p} />;
export const Trash2 = (p: IconProps) => <Icon name="trash2" {...p} />;
export const Save = (p: IconProps) => <Icon name="save" {...p} />;
export const Plus = (p: IconProps) => <Icon name="plus" {...p} />;
export const LayoutList = (p: IconProps) => <Icon name="layout-list" {...p} />;
export const ArrowUp = (p: IconProps) => <Icon name="arrow-up" {...p} />;
export const ArrowLeft = (p: IconProps) => <Icon name="arrow-left" {...p} />;
export const ArrowDown = (p: IconProps) => <Icon name="arrow-down" {...p} />;
export const ArrowUpDown = (p: IconProps) => <Icon name="arrow-up-down" {...p} />;
export const ChevronRight = (p: IconProps) => <Icon name="chevron-right" {...p} />;
export const CheckCircle = (p: IconProps) => <Icon name="check-circle" {...p} />;
export const AlertCircle = (p: IconProps) => <Icon name="alert-circle" {...p} />;
export const Info = (p: IconProps) => <Icon name="info" {...p} />;
export const Loader2 = (p: IconProps) => <Icon name="loader-2" {...p} />;
export const Clock = (p: IconProps) => <Icon name="clock" {...p} />;
export const Check = (p: IconProps) => <Icon name="check" {...p} />;
export const HelpCircle = (p: IconProps) => <Icon name="help-circle" {...p} />;
export const LogOut = (p: IconProps) => <Icon name="log-out" {...p} />;
export const FolderPlus = (p: IconProps) => <Icon name="folder-plus" {...p} />;
export const Download = (p: IconProps) => <Icon name="download" {...p} />;
export const Upload = (p: IconProps) => <Icon name="upload" {...p} />;
