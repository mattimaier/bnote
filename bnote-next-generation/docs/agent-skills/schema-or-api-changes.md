# Schema or API Changes

Use for payload, module action, endpoint, or DB-contract changes.

- Confirm current contract in `docs/API_ENDPOINTS.md` and handler in `api/modules/*.php`.
- Implement backend and frontend contract changes together (avoid half-migrations).
- Preserve or strengthen auth/permission checks and error shape consistency.
- Validate rights management explicitly: test at least one allowed and one denied permission path.
- Update any calling adapters/clients (`frontend/lib/*-api.ts`, shared fetch helpers).
- Document contract changes in `docs/API_ENDPOINTS.md` (or note explicit deferral).
- Validate with targeted request/response checks and frontend smoke flow.
- Required output: old vs new contract, compatibility risk, verification evidence.

If UI is impacted, run mobile smoke checks on affected routes.
