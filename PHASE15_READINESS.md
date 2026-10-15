# Phase 15A — Production Readiness & Joomla Integration Audit

**Status: prepared. No live change made. Nothing published or purchasable.**

---

## 1 · Deployment state & Phase 14 migrations

Two migrations added since Phase 13B (`b680781`), both additive, both safe to run
on a live DB:

| Migration | Adds | Risk |
|---|---|---|
| `…_130839_add_source_keys_to_catalogue` | `source_key` (nullable, indexed) on `course_modules`, `lessons`, `materials` | additive only |
| `…_140538_add_commercial_fields_to_course_variants` | `price_cents` → nullable; +`source_key`,`approval_status`,`duration`,`inclusions`,`price_source`,`checkout_ref` on `course_variants` | `price_cents` nullability change — verify MySQL accepts `->change()` without doctrine/dbal (Laravel 11+ native) |

App-code delta since `b680781`: `app/Domain/Import/`, `CatalogueImport`,
`CatalogueVariants`, `Admin\Variants` + view, 2 configs, `routes/web.php`,
model fillable edits, `MigrateRunnerController` (allowlist + per-command flags).
**No `public/` change** → the public archive does not need re-upload for a code-only
refresh unless `index.php`/assets changed (they did not).

## 2 · Incremental InterServer deployment (no SSH, docroot untouched)

The existing docroot `/home/euroafri/academy.euroafricamedia.eu/` is **not
modified**. Only the private tree `/home/euroafri/eamn-academy/` gets new code.

### Build
```bash
./build.sh           # produces dist/eamn-academy-app-<stamp>.tar.gz (+ public + manifest)
```
`build.sh` already excludes `public/`, `.env*`, `tests`, `node_modules`, `.git`,
runtime storage — and never packages secrets. `vendor/` ships inside the app
archive so no composer on host.

### Upload (cPanel File Manager, not FTP — chroot)
1. Upload `eamn-academy-app-<stamp>.tar.gz` to `/home/euroafri/`.
2. Extract → it produces/overlays `/home/euroafri/eamn-academy/` (private sibling
   of the docroot). **Existing `.env` at that path is preserved** — the archive
   contains no `.env` (stripped at build).
3. Upload the extracted client archive `EAMN Academy+Training.rar` **contents**
   to `/home/euroafri/eamn-academy/storage/app/import-source/` (the importer's
   default `IMPORT_SOURCE_ROOT`). Private — outside the docroot.

### Preserve prod secrets
- `.env` + `APP_KEY` live only on the host; never in an archive. `build.sh`
  strips `.env*` (keeps only `.env.production.example`).
- DB creds, PayPal sandbox creds, `JOOMLA_BRIDGE_SECRET` stay as already set.

## 3 · Safe execution sequence (no SSH — via gated runner)

`POST /deploy/migrate` is now **per-command** once-gated (`deploy.done.<md5>`),
IP+token allowlisted, audit-logged. Allowlist extended to include the two
idempotent catalogue commands. Run in order, each once:

```
1. {run: "migrate --force"}                  → applies the 2 Phase-14 migrations
2. {run: "catalogue:import"}                  → 7 draft courses, 34 mod, 137 les,
                                                57 quiz, 23 private materials
3. {run: "catalogue:variants"}                → 17 draft+pending variants
4. (optional) {run: "db:seed --class=RoleSeeder --force"}  → if roles missing
```

`catalogue:import` reads `storage/app/import-source/` by default — **no
`--source` arg needed** (the runner can't pass args anyway). To re-run a command
(e.g. after fixing source files) delete `storage/app/private/deploy.done.<md5>`
via File Manager, then re-POST.

**Verify on host afterwards:**
- `GET /up` → 200
- `GET /` → 302 → `/login`
- `GET /.env`, `/vendor/…`, `/storage/…` → 404 (dotfile + private deny)
- Admin → `/admin/packages` lists 17 variants, all `draft`/`pending`.

## 4 · Importer on shared hosting — confirmed feasible

- No SSH needed: `catalogue:import` runs through the web-gated `deploy/migrate`
  runner (added to allowlist this phase).
- Reads DOCX via PHP `ZipArchive`+`DOMDocument` — both already required by
  `build.sh` preflight (`zip`, `xml`). No Composer dep, no binary.
- Source files uploaded via File Manager to `storage/app/import-source/`.
- Writes go to `local` disk = `storage/app/private/` — outside docroot, never
  web-accessible; delivery only via `files.materials` controller.
- Idempotent — safe to re-run; dry-run not needed on host (runner runs it
  for-real but it's a no-op on re-run).

## 5 · Sync keeps everything unpublished

Importer + variant seeder force `status=draft`, `approval_status=pending` on
every row, on every run. `isPurchasable()` requires `published + approved +
priced + course published` — so nothing imported is buyable until an admin
explicitly approves and publishes both course and variant.

## 6 · Joomla pages — component identified

All seven Academy sales pages are **SP Page Builder pages**
(`com_sppagebuilder&view=page&id=N`), **not** Joomla articles and **not** SP
LMS items. (Legacy SP LMS at `/component/splms/course/N-…` sells unrelated
$49.99 courses — separate system, do not touch.) Page-body edits happen in
**SP Page Builder → Pages** in Joomla admin, or via the page's CTA/button
addon — not the article editor.

**Confirmed public URLs (live, fetched 2026-10-15):**

| # | Joomla URL | SP Page Builder | Page type |
|---|---|---|---|
| 1 | `/courses/training-awareness-institute` | yes | programme **hub** — links to `/courses`, no product |
| 2 | `/courses/special-german-language-school` | yes | school marketing — CTAs to `/courses/training`+contact |
| 3 | `/courses/training` | yes | Cultural-Competence **B2B seminar** enquiry page |
| 4 | `/courses/digital-inclusion-training-for-elderly` | yes | Elderly Digital Inclusion — matches shell |
| 5 | `/courses/start-small-earn-big` | yes | **Book** sales page (Amazon), course = inside #6 |
| 6 | `/courses/business-wealth-academy` | yes (`id=46`) | Wealth/Start-Small hub |
| 7 | `/courses/no-easy-target™-security-academy` | yes | Security Academy marketing |

## 7 · Joomla → variant mapping

CTA target format: `https://academy.euroafricamedia.eu/checkout/{variant_id}`
(variant resolved server-side; package survives SSO via `url.intended`).
**Until a variant is `approved`+`published`+priced, the button must be a
"Register interest" link to `/contact` — never a buy link.**

| Joomla page | Academy course | Variants → button | Price | checkout_ref (`source_key`) | Page edits needed | Status |
|---|---|---|---|---|---|---|
| `special-german-language-school` | `german-courses` | A1·A2·Nurses·Living·Medical | €79·€99·€149·€99·€199 | `german-courses-{a1-complete,a2,german-for-nurses,living-in-germany,medical-german}` | 5 self-paced price cards + 2 live (TBD) | pending |
| | | Intensive + Conversation Club | TBD | `german-courses-{intensive,conversation-club}` | add after price fixed | pending |
| `digital-inclusion-training-for-elderly` | `elderly-digital-inclusion` | — programme doc only | none | — | enquiry-only; no self-serve checkout yet (course shell empty) | pending |
| `start-small-earn-big` | `start-small-earn-big-africa` | Standard | **none** | `start-small-…-standard` | set price first; book page stays Amazon | pending |
| `business-wealth-academy` | `build-financial-security-germany` + Start Small | Standard | **none** | `build-…-standard` | add price card after fix | pending |
| `no-easy-target™-security-academy` | `no-easy-target` | Standard | **none** | `no-easy-target-standard` | add price card; **module-count fix** | pending |
| `training` (cultural competence) | — B2B seminar, no self-serve | — | POA | — | keep "Request proposal" → contact | n/a |
| `training-awareness-institute` | — hub, no single course | — | — | — | links to `/courses`; no buy button | n/a |

## 8 · Gaps & mismatches (do not go live until resolved)

| # | Issue | Detail | Action |
|---|---|---|---|
| A | **No flagship sales page** | "Intercultural Success in German Healthcare" (10-mod flagship, 4 tiers) has **no** Joomla page — only generic cultural-competence seminar page exists | create SP Page Builder page for flagship, or map tiers onto `training` page |
| B | **Module-count mismatch** | No Easy Target page advertises "**10** modules"; imported manual has **8** modules / 24 lessons | reconcile copy vs actual curriculum |
| C | **3 variants no price** | `no-easy-target-standard`, `build-…-standard`, `start-small-…-standard` | admin must set price |
| D | **4 variants price range** | `…-institutional` (€1500–5000), `digital-standard` (€99–139), `german-intensive` (€299–399), `german-club` (€10–15) | pick fixed figure or POA |
| E | **3 empty course shells** | `digital-skills-training`, `elderly-digital-inclusion`, `german-courses` — 0 modules/lessons | author curriculum or keep as enquiry-only |
| F | **Checkout uses variant_id** | Joomla button needs the numeric variant `id` from `/admin/packages`, not the slug — IDs only exist after seeding on host | read real IDs post-seed |

## 9 · Backup & rollback

**Code** — keep prior `eamn-academy-app-*.tar.gz` + `eamn-academy-public-*.tar.gz`;
rollback = re-extract both. **DB** — cPanel/phpMyAdmin export before migrate;
Phase-14 migrations are additive (new nullable cols) → rollback needs no data
loss, but restore snapshot to drop imported rows. **Joomla** — before editing
any of the 7 SP Page Builder pages: duplicate the page (SP Page Builder →
Pages → Duplicate) or export its JSON; rollback = restore the duplicate.

## 10 · Hard stops (unchanged)
- No live Joomla page edited — mapping only.
- No variant approved/published — all `draft`+`pending`.
- `PAYPAL_MODE=sandbox` — Live not enabled.
- Existing docroot + `.env` + `APP_KEY` untouched.
