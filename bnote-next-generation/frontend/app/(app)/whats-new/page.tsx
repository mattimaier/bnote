import { redirect } from "next/navigation";

export default function WhatsNewRedirectPage() {
  redirect("/changelog/");
}
