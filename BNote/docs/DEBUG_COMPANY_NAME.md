# Debugging company / band name

The login page shows “Welcome to {band name}”. The band name comes from **config/company.xml** (`<Name>`) via the **getPublicConfig** API.

## 1. Login page with `?debug=1`

1. Open the login page with `?debug=1`, e.g.  
   `https://your-host/.../next/login.html?debug=1`
2. Open DevTools → **Console**.
3. Look for `[BNote company debug]` logs:
   - **raw** – Full API response
   - **config** – Normalized config object
   - **config.company** – Company value used for “Welcome to …”
   - **_debug** (if present) – Backend debug info
   - **company (used)** – Value passed into the welcome string
   - **welcomeText** – Final “Welcome to …” string

## 2. Call getPublicConfig directly

**Browser:**  
`https://your-host/.../next/api/index.php?module=auth&action=getPublicConfig`

**With backend debug info:**  
`...&debug=1`

**curl:**
```bash
curl -s "https://your-host/.../next/api/index.php?module=auth&action=getPublicConfig&debug=1"
```

Check the JSON:
- **company** – Band name from config
- **_debug** (when `debug=1`):
  - **company_from_getCompany** – Value from `getCompany()`
  - **config_path** – `config/company.xml`
  - **config_abs_path** – Absolute path used by PHP
  - **config_exists** – Whether the file exists
  - **config_readable** – Whether the file is readable

## 3. Check config/company.xml

- Path: **BNote/config/company.xml** (relative to project root).
- The API runs with `chdir` to the project root, so that path is used.
- Ensure the file exists and contains a `<Name>` element, e.g.:

  ```xml
  <Company>
    <Name>Your Band Name</Name>
    ...
  </Company>
  ```

If the file is missing or `<Name>` is empty, “Welcome to …” will show nothing after “Welcome to”.

## 4. Common issues

| Symptom | Likely cause |
|--------|---------------|
| `config.company` empty, `_debug.config_exists` false | **config/company.xml** missing or wrong path |
| `config_exists` true but `company` empty | No `<Name>` or empty `<Name>` in **config/company.xml** |
| `raw` has `company` but welcome still wrong | JS bug (check **company (used)** and **welcomeText** in console) |
| API returns `{ success, data }` but login uses wrong object | Login uses `data`; fetch fallback must use `json.data` (already handled) |

## 5. Disable debug

Remove `?debug=1` from the login URL. The API ignores `debug` unless explicitly requested.
