# Admin Walkthrough (90s) - Recording Runbook

## Goal

Produce a 90-second, admin-focused product walkthrough for website embedding.

## Locked Specs

- Base URL: `http://localhost:3000/bnote-next-generation/`
- Format: `16:9` landscape
- Capture target: `1920x1080`, `30fps`
- Recorder: QuickTime
- Audience: admins/organizers
- Language: English
- Narration: synthetic voiceover (added in edit)

## Pre-Flight Setup (3-5 minutes)

1. Start app locally and confirm these routes load:
   - `/login/`
   - `/dashboard/`
   - `/users/`
   - `/contacts/`
   - `/contacts/integration/`
   - `/system-information/`
   - `/changelog/`
2. Set browser window to a clean 16:9 capture frame (roughly `1720x970` visible area).
3. Use one tab only; close devtools, extra extensions, popups, and notifications.
4. Log in with demo user:
   - Username: `stefan`
   - Password: `c88M4CCNEZ86H6DL`
5. Set theme and language once, then keep them unchanged during recording.
6. Keep cursor movement slow and deliberate.

## Recording Sequence (One-Take)

Use `docs/video/admin-walkthrough-90s-shotlist.csv` as the exact timeline.

## Fast Recovery Rules

- If a page load exceeds 1.5s, hold the current frame and continue timing.
- If a click misfires, pause cursor for 1 second, then continue (cut in edit).
- If any sensitive data appears unexpectedly, stop and restart from Scene 1.

## Post-Capture Deliverables

1. Raw screen recording (`.mov` from QuickTime).
2. Edited master (`.mp4`) with synthetic VO and captions.
3. Optional web poster frame (`.jpg`) from final CTA scene.

## Export Settings

- Codec: H.264
- Resolution: `1920x1080`
- Frame rate: `30fps`
- Video bitrate target: `~10 Mbps`
- Audio: stereo AAC
- Runtime target: `85-95s`
