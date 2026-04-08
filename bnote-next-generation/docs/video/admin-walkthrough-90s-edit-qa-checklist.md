# Admin Walkthrough (90s) - Edit and QA Checklist

## Editing Checklist
1. Remove dead time between route transitions.
2. Keep cursor speed calm and intentional.
3. Add synthetic voiceover from `admin-walkthrough-90s-voiceover.txt`.
4. Add captions from `admin-walkthrough-90s-captions.srt`.
5. Add subtle background music bed:
   - keep music around `-22` to `-26 LUFS` beneath voiceover.
6. Keep scene timing aligned with shotlist; final duration `85-95s`.

## Visual QA
1. No clipped cards, modals, or headers.
2. No horizontal overflow.
3. No debug-only pages, overlays, or dev artifacts.
4. No private or sensitive data appears.
5. Branding, topbar, and key admin UI remain readable at 1080p.

## Message QA
1. First 20 seconds clearly communicate admin value.
2. Users and Contacts/Phase in are visibly demonstrated.
3. System Information and Changelog are shown as trust/transparency proof points.
4. Final CTA frame holds for at least 2.5 seconds.

## Export QA
1. Export format: H.264 MP4
2. Resolution: `1920x1080`
3. Frame rate: `30fps`
4. Bitrate target: `~10 Mbps`
5. Confirm smooth playback in website embed context before publish.
