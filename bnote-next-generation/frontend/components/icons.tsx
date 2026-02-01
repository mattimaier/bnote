/**
 * BNote Next Generation - Icon mapping (Lucide)
 * Parity with vanilla app (data-lucide) and entity-config.json.
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import {
  LayoutDashboard,
  Users,
  User,
  CalendarDays,
  MessageSquare,
  Music,
  Calendar,
  MapPin,
  ChevronLeft,
  Search,
  UserCircle,
  CheckSquare,
  Package,
  Map,
  Plus,
  X,
  ArrowLeft,
  Sun,
  Moon,
  Menu,
  Pencil,
  Trash2,
  Key,
  ChevronRight,
  ChevronDown,
  MoreVertical,
  FileText,
  Printer,
  ShieldAlert,
  Mail,
  Phone,
  Tag,
  Bell,
  type LucideIcon,
} from "lucide-react";

const iconMap: Record<string, LucideIcon> = {
  "layout-dashboard": LayoutDashboard,
  dashboard: LayoutDashboard,
  users: Users,
  user: User,
  "user-cog": User,
  "calendar-days": CalendarDays,
  "message-square": MessageSquare,
  music: Music,
  trumpet: Music, // Lucide has no trumpet; entity-config uses "trumpet" for concert
  calendar: Calendar,
  "map-pin": MapPin,
  "chevron-left": ChevronLeft,
  search: Search,
  "user-circle": UserCircle,
  "check-square": CheckSquare,
  package: Package,
  map: Map,
  plus: Plus,
  x: X,
  "arrow-left": ArrowLeft,
  sun: Sun,
  moon: Moon,
  menu: Menu,
  pencil: Pencil,
  trash2: Trash2,
  key: Key,
  "chevron-right": ChevronRight,
  "chevron-down": ChevronDown,
  "more-vertical": MoreVertical,
  "file-text": FileText,
  printer: Printer,
  "shield-alert": ShieldAlert,
  mail: Mail,
  phone: Phone,
  tag: Tag,
  bell: Bell,
};

export function getIcon(name: string): LucideIcon {
  if (!name) return LayoutDashboard;
  const key = String(name).toLowerCase().trim();
  return iconMap[key] ?? iconMap[name] ?? LayoutDashboard;
}

export {
  LayoutDashboard,
  Users,
  User,
  CalendarDays,
  MessageSquare,
  Music,
  Calendar,
  MapPin,
  ChevronLeft,
  Search,
  UserCircle,
  CheckSquare,
  Package,
  Map,
};
