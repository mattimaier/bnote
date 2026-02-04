/**
 * BNote Next Generation - Profile edit route
 * Edit mode is represented in the URL (same pattern as entity detail: /entity/type/id/edit).
 * This route renders the same ProfilePage; the component derives isEditing from pathname.
 *
 * Copyright (C) 2026 BNote Contributors
 */

import ProfilePage from "../page";

export default function ProfileEditPage() {
  return <ProfilePage />;
}
