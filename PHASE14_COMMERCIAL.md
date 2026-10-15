# Phase 14 — Commercial Catalogue, Joomla Mapping & Checkout Audit

**Status: prepared, all draft + pending-approval. Nothing is purchasable or published.**

Source of truth: `config/commercial-packages.php` → seeded by
`php artisan catalogue:variants` (idempotent on `source_key`).

---

## 1 · Commercial catalogue matrix

Every price/duration/inclusion is copied verbatim from the client archive.
`price_source` on each variant names the exact file + section.

| Course | Variant | Delivery | Price (EUR) | Duration | Source ref | Approval |
|---|---|---|---|---|---|---|
| **Intercultural Success in German Healthcare** | Essential | prerecorded | **99.00** | self-study ~25–30 h | Master Plan §3 "Essential — €99" | pending |
| | Professional | hybrid | **249.00** | self-study + live monthly | §3 "Professional — €249" | pending |
| | Premium | live | **599.00** | 6–8-wk cohort | §3 "Premium — €599" (3×€209 option) | pending |
| | Institutional | private | **— range** | customised | §3 "€1,500–5,000+" | **pending — no fixed price** |
| **No Easy Target** | Standard | live | **— none** | 36 h taught + 12 h planning | Manual "Duration" — NO price | **pending — no price in source** |
| **Build Financial Security in Germany** | Standard | live | **— none** | 8 mod / 24 les | Manual "Offer Ladder" heading empty | **pending — no price in source** |
| **Start Small, Earn Big in Africa** | Standard | live | **— none** | 8 mod / 32 les | Workbook — NO price | **pending — no price in source** |
| **Digital Skills Training** | Early Bird | live | **49.00** | 6 weeks | Skills Pack "Early Bird €49" | pending |
| | Standard | live | **— range** | 6 weeks | Skills Pack "€99–139" | **pending — range** |
| | VIP Package | live | **229.00** | 6 weeks | Skills Pack "VIP €229" | pending |
| **German Courses** | A1 Complete | prerecorded | **79.00** | self-paced | German School "A1 €79" | pending |
| | German A2 | prerecorded | **99.00** | self-paced | "A2 €99" | pending |
| | German for Nurses | prerecorded | **149.00** | self-paced | "German for Nurses €149" | pending |
| | Living in Germany | prerecorded | **99.00** | self-paced | "Living in Germany €99" | pending |
| | Medical German | prerecorded | **199.00** | self-paced | "Medical German €199" | pending |
| | Intensive (8-wk) | live | **— range** | 90 min 2×/wk · 8 wk | "Intensive €299–399" | **pending — range** |
| | Conversation Club | live | **— range** | 1 h Sat | "Club €10–15" | **pending — range** |

**Fixed-price rows:** 9 · **Range/quote rows (POA or fix needed):** 5 · **No-price rows:** 3.
**All 17 variants: `status=draft`, `approval_status=pending`.**

## 2 · Pending-approval list (must NOT go live as-is)

| # | Item | Gap | Needed decision |
|---|---|---|---|
| 1 | `ihc-…-institutional` | price is a range €1,500–5,000 | set fixed price or enable "Request quote" flow |
| 2 | `no-easy-target-standard` | **no price in source** | admin sets price |
| 3 | `build-financial-security-germany-standard` | **no price in source** | admin sets price |
| 4 | `start-small-earn-big-africa-standard` | **no price in source** | admin sets price |
| 5 | `digital-skills-training-standard` | range €99–139 | pick one figure |
| 6 | `german-courses-intensive` | range €299–399 | pick one figure |
| 7 | `german-courses-conversation-club` | range €10–15 | pick one figure |
| 8 | `digital-skills-training` / `elderly-digital-inclusion` / `german-courses` course shells | **0 modules/lessons** — source has no Module/Lesson structure | curriculum must be authored before publish |
| 9 | all variants | none reviewed | admin approve → then set `status=published` |

## 3 · Joomla page → variant map

Joomla front site `https://euroafricamedia.eu` (unchanged). Each sales page CTA
points to `https://academy.euroafricamedia.eu/checkout/{variant}` — but **only
after** that variant is `approved`+`published`. Until then the button must stay
a "Register interest" link, not a buy link.

> ⚠ **Confirm actual article URLs in Joomla admin** — slug guesses below.

| Joomla page (existing URL — preserved) | Course / programme | Packages to show | Price cards | Checkout ref | Text changes needed | Status |
|---|---|---|---|---|---|---|
| `/…/intercultural-healthcare` (flagship) | Intercultural Success in German Healthcare | Essential / Professional / Premium / Institutional | €99 · €249 · €599 · POA | `…-essential` `-professional` `-premium` `-institutional` | add 4-tier price cards; Institutional → "Request quote" | pending |
| `/…/security` or `/no-easy-target` | No Easy Target Security Academy | Standard | **TBD** | `no-easy-target-standard` | set price card after §2 #2 | pending |
| `/…/wealth` or `/financial-security` | Build Financial Security in Germany | Standard | **TBD** | `build-…-standard` | add price after #3 | pending |
| `/…/start-small-earn-big` | Start Small, Earn Big in Africa | Standard | **TBD** | `start-small-…-standard` | add price after #4 | pending |
| `/…/digital-skills` | Digital Skills Training | Early Bird / Standard / VIP | €49 · TBD · €229 | `…-early-bird` `-standard` `-vip` | 3 cards; fix Standard after #5 | pending |
| `/…/elderly-digital-inclusion` | Elderly Digital Inclusion | — (programme doc only) | none | — | marketing page; enrolment via enquiry, no self-serve checkout yet | pending |
| `/…/german-school` | EAMN German Language School | A1·A2·Nurses·Living·Medical + Intensive + Club | €79·€99·€149·€99·€199 · TBD · TBD | `german-courses-*` | 5 self-paced cards + 2 live cards after #6 #7 | pending |

**Checkout identifier** = `variant.checkout_ref` (= `source_key`), stable, never
changes on re-seed. Joomla links should carry `?variant={checkout_ref}` or the
route `checkout/{variant_id}`.

## 4 · Checkout integration audit

```
Joomla CTA  ──►  academy.euroafricamedia.eu/checkout/{variant}
                    │  (auth middleware) not logged in?
                    ▼
              auth/bridge/start?target=/checkout/{variant}
                    │  state=session nonce · url.intended=checkout
                    ▼  Joomla com_eamnbridge?task=go
              Joomla login → issues one-time code bound to same state
                    ▼
              auth/bridge/consume  (state check → server code exchange)
                    │  Auth::login  →  redirect()->intended()
                    ▼  back to /checkout/{variant} — package preserved ✓
              POST checkout/{variant}
                    │  OrderService::create reads price SERVER-SIDE
                    │  from CourseVariant — client never sends amount
                    ▼
              PayPalGateway::createCheckout → PayPal approve
                    ▼  webhook PAYMENT.CAPTURE.COMPLETED (verified, idempotent)
              OrderService::fulfil → Enrolment created
```

**Integrity controls already in place:**
- `OrderService::create()` (line 42–53) reads `price_cents` from the variant —
  **client-supplied amounts are never used**.
- `CheckoutController::show/store` `abort_unless($variant->isPurchasable())` —
  now requires `status=published AND approval_status=approved`.
- `paypalReturn` captures **server-side**; the return page grants nothing.
- Webhook fulfilment is idempotent via unique `(gateway, gateway_event_id)`.
- `PAYMENT_GATEWAY=paypal`, `PAYPAL_MODE=sandbox` — **Live not enabled**.

**Package preservation through SSO:** `url.intended` is set in
`BridgeStartController` before the Joomla round-trip and consumed by
`redirect()->intended()` in `BridgeConsumeController` — the selected variant
survives login/registration.

## 5 · Admin approval workflow

`Admin\Variants` (`/admin/packages`) provides the full review gate:

| Capability | Implementation |
|---|---|
| List every variant + course, source ref, inclusions | `Variants::render` |
| Edit name / price / duration inline | `Variants::edit` → `save` |
| Approve commercial content | `Variants::approve` — **blocked if no price** |
| De-approve | `Variants::unapprove` — also force-unpublishes |
| Publish | `Variants::togglePublish` — **blocked unless approved + priced** |
| Toggle course draft↔published | `Admin\Courses::toggleStatus` |
| Assign trainer | `TrainerAssignment` (model) — wire to `Trainer\CourseManage` |
| Review materials | `Material` + `files.material` |

`isPurchasable()` = `published AND approved AND priced AND course published` —
re-checked inside `OrderService::create`, so a URL alone can never buy a
pending variant.

## 6 · Deployment / runbook

```bash
# on the server (after Phase 13 split deploy)
php artisan migrate --force                 # adds source_key + commercial cols
php artisan catalogue:import --dry-run \     # review log — must be all EXISTS
    --source=/path/to/import-source
php artisan catalogue:variants --dry-run     # review 17 planned variants
php artisan catalogue:variants               # seed draft+pending variants
```

Then in **admin UI**: fix the 5 pending prices → approve each variant → publish
variants + courses → only then add Joomla buy-links. **Do not** point Joomla CTAs
at checkout until the variant is purchasable, or buyers hit a 404.

**Hard stops (unchanged):** no live Joomla edit, no PayPal Live, no production
content change — approval + publish is a manual admin step.
