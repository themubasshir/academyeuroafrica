---
name: EAMN Academy
description: A private learning register for EuroAfrica Media Network — institutional navy shell, ivory paper ground, serif display record-keeping.
colors:
  ink-950: "#131a24"
  ink-900: "#1c2431"
  ink-700: "#33415c"
  ink-600: "#4c4a44"
  ink-500: "#6b685f"
  ink-400: "#8f8c82"
  navy-950: "#081c33"
  navy-900: "#0c2b4c"
  navy-800: "#08355c"
  navy-100: "#d7e7f5"
  navy-50: "#eef4fa"
  reg-700: "#0345bf"
  reg-600: "#1450d6"
  reg-100: "#dfe9fb"
  reg-50: "#eff4fd"
  seal-600: "#a97e2f"
  seal-500: "#c8a24a"
  seal-400: "#d8b96a"
  seal-100: "#f4ead0"
  seal-50: "#faf4e4"
  paper-50: "#faf8f3"
  paper-100: "#f4f1e9"
  paper-200: "#e9e4d7"
  paper-300: "#d9d2c0"
typography:
  display:
    fontFamily: "Source Serif 4, Iowan Old Style, Georgia, serif"
    fontSize: "1.75rem"
    fontWeight: 600
    lineHeight: 1.2
  headline:
    fontFamily: "Source Serif 4, Iowan Old Style, Georgia, serif"
    fontSize: "1.125rem"
    fontWeight: 600
    lineHeight: 1.375
  body:
    fontFamily: "Instrument Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: "Instrument Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.6875rem"
    fontWeight: 600
    letterSpacing: "0.18em"
rounded:
  sm: "2px"
  md: "6px"
  lg: "8px"
spacing:
  card-pad: "20px"
  ledger-row: "16px 20px"
components:
  button-primary:
    backgroundColor: "{colors.reg-700}"
    textColor: "#ffffff"
    rounded: "{rounded.md}"
    padding: "10px 16px"
  button-primary-hover:
    backgroundColor: "{colors.reg-600}"
  button-secondary:
    backgroundColor: "#ffffff"
    textColor: "{colors.ink-700}"
    rounded: "{rounded.md}"
    padding: "10px 16px"
  button-gold:
    backgroundColor: "{colors.seal-400}"
    textColor: "{colors.ink-950}"
    rounded: "{rounded.md}"
    padding: "10px 16px"
  card:
    backgroundColor: "#ffffff"
    rounded: "{rounded.lg}"
  input:
    backgroundColor: "#ffffff"
    textColor: "{colors.ink-900}"
    rounded: "{rounded.md}"
---

# Design System: EAMN Academy

## Overview

**Creative North Star: "The Academy Register"**

The interface behaves like an institutional registry: a bound ledger of courses, enrolments, results, and credit. A committed navy shell (#08355c, the authentic EuroAfrica header blue) holds an ivory paper ground where every record is ruled, numbered, and stamped. Serif display type (Source Serif 4) gives headings the weight of an engraved title page; spaced small-caps labels mark each surface like a register's section head.

Density is calm but information-forward — rows are tight, hairlines separate entries, and decoration is limited to rules, folios, and the gold seal. Nothing glows, floats, or gradients. The system replaces generic dashboard chrome with the feeling of a well-kept academic record office.

**Key Characteristics:**
- Navy rail + ivory paper ground; never reversed mid-flow
- Hairline `.ledger` rows instead of floating cards for record lists
- `.register` small-caps labels and `.folio` record numbers as signature marks
- Seal gold reserved for stamp moments (active states, seals, rare accents)
- Serif display confined to headings; everything else is Instrument Sans

## Colors

The palette pairs institutional navy with ivory paper; registry blue carries all action.

### Primary
- **Registry Blue** (`reg-700`): every primary action and link. Buttons, SSO call-to-action, interactive affordances. One voice per screen — it is the pen that writes in the register.
- **Registry Blue Hover** (`reg-600`): hover state for primary actions; also the global focus-ring color.

### Secondary
- **EuroAfrica Navy** (`navy-800` `#08355c`): the authentic brand header blue. App shell rail, login field, active nav background. Structural, not interactive.
- **Deep Navy** (`navy-900`/`navy-950`): pressed states, page-backdrop depth behind the rail.

### Tertiary
- **Seal Gold** (`seal-500`): stamp and accent only — active tab underlines, badge seals, inset shadows on active nav. Never a background for body text.
- **Seal Gold Soft** (`seal-400`): `.btn-gold` background for ceremonial actions (checkout, completion).

### Neutral
- **Ivory Paper** (`paper-50`): the ground. Body background, card wells.
- **Parchment Steps** (`paper-100`→`300`): hover fills, row dividers, hairline borders.
- **Ink** (`ink-950`→`400`): text ramp. `ink-950` headings, `ink-900`/`700` body, `ink-600`/`500` secondary (never below `ink-600` for text under 14px).
- **Navy Tint** (`navy-100`): text on the navy shell; always at full opacity or `/85` minimum for contrast.

### Named Rules
**The One Voice Rule.** Registry blue is the only action color. Gold is a seal, not a button color — except `.btn-gold`, reserved for checkout and completion ceremonies.

**The Paper Rule.** Content lives on paper or white cards. Navy never appears inside the content column; it is the shelf, not the page.

## Typography

**Display Font:** Source Serif 4 (self-hosted, weights 500–600)
**Body Font:** Instrument Sans (system fallbacks)
**Ledger Font:** Instrument Sans, letterspaced uppercase (folios, register labels — no mono face)

**Character:** Engraved serif titles over a quiet grotesque; the pairing reads institutional, not editorial-fashion.

### Hierarchy
- **Display** (semibold, `text-2xl`→`1.75rem`, tight): page titles — "Welcome back to the register."
- **Headline** (semibold, `text-lg`): card and section titles.
- **Title** (semibold, `text-sm`, sans): `.h3`, row-lead emphasis.
- **Body** (regular, `text-sm`): default reading size; secondary text in `ink-600`.
- **Register Label** (semibold, `11px`, `0.18em` tracking, uppercase): `.register` — spaced small-caps surface titles, always paired with a hairline rule.
- **Folio** (medium, `11px`, `0.12em` tracking, uppercase): `.folio` — stamped record references, dividers, meta lines.

### Named Rules
**The Serif Boundary Rule.** Source Serif 4 appears only in `.h1`/`.h2` and the brand wordmark context. Buttons, tables, badges, and nav stay in Instrument Sans.

## Layout

Desktop shell: fixed navy left rail (brand lockup top, nav, user plate bottom) + content column on `paper-50`. Content column max-widths are generous (`max-w-5xl/6xl`) with `px-4 sm:px-6 lg:px-8` gutters. Mobile: rail collapses to a top bar with drawer; brand lockup persists in both.

Surfaces open with a `.register` label + hairline, then `.h1`, then supporting line — the same title-page rhythm on every screen. Record lists use `.ledger` (hairline-divided rows, `px-5 py-4`); grouped content uses `.card` / `.card-pad` (`p-5 sm:p-6`).

Trainer course sections share a single tab strip (`<x-trainer.course-nav>`) with seal-gold active underlines — one navigation spine, never per-page bespoke tabs.

## Elevation & Depth

Near-flat. Depth comes from the navy/paper tonal split and one whisper shadow on cards (`0 1px 2px rgba(19,26,36,0.05)`). The login panel alone earns a deeper shadow (`0 20px 50px rgba(4,16,32,0.5)`) to lift it off the navy field — the one theatrical moment permitted.

### Shadow Vocabulary
- **Card rest** (`0 1px 2px rgba(19,26,36,0.05)`): separation from paper ground; not atmosphere.
- **Panel lift** (`0 20px 50px rgba(4,16,32,0.5)`): login/entry panels over navy only.

### Named Rules
**The Flat Register Rule.** Ledger rows, tables, and nav are hairline-ruled, never shadowed. Shadow means "detached sheet," and only the sign-in sheet detaches.

## Shapes

Small radii throughout — records are squared-off entries, not pills. `rounded-md` (6px) on buttons and inputs, `rounded-lg` (8px) on cards and the login panel, `rounded-sm` (2px) on badges. The only circles are status dots and the avatar plate. Borders are always hairline `paper-300` (or navy-700 on the shell); no thick strokes, no dashed ornament.

## Components

### Buttons
- **Shape:** gently squared (6px radius), `px-4 py-2.5`, semibold `text-sm`.
- **Primary:** registry blue (`reg-700` → `reg-600` hover → `navy-900` active), white text.
- **Secondary:** white on paper-300 border, ink-700 text.
- **Gold:** seal-400 bg, ink-950 text — checkout/completion only.
- **Ghost / Danger:** reg-700 text on reg-50 hover; red-700 for destructive.
- **Focus:** all variants carry a 2px `reg-600` focus ring, offset 2px.
- Icon-only actions are ≥44px square (`h-11 w-11`).

### Badges
- **Style:** squared chip (`rounded-sm`), `text-xs` semibold, tonal background + matching border + darker text (`badge-blue/green/amber/red/gold/gray`).
- Used for status, roles, delivery types — the register's rubber stamps.

### Cards / Containers
- **Corner:** 8px radius.
- **Background:** white on paper ground; `paper-50` header wells inside cards.
- **Border:** 1px `paper-300`; hairline `paper-200` internal dividers.
- **Padding:** `p-5 sm:p-6` via `.card-pad`.

### Inputs / Fields
- **Style:** white bg, `paper-300` border, 6px radius, `text-sm`.
- **Focus:** `reg-600` border + 2px `reg-600/25` ring.
- **Disabled:** `paper-100` bg, `ink-400` text.
- **Labels:** `.label`, `text-sm` medium `ink-700`, `mb-1.5`.

### Navigation
- **Rail:** navy-800; brand lockup top (`<x-ui.brand dark>`), links in navy-100 with icon at 80% opacity; active = navy-850/900 fill + seal-gold inset edge.
- **Course tabs:** `course-nav` — paper-side tab strip, active = seal-500 underline + ink-950 semibold label; `aria-current="page"`.
- **Mobile:** top bar + slide drawer; same lockup and link treatment.

### Ledger (signature)
Hairline-ruled record list (`.ledger` / `.ledger-row`): rows separated by `paper-200` dividers inside a `paper-300`-bordered sheet; hover shifts the row to `paper-50`. Used for enrolments, results, students, bookings — anywhere the register "lists entries."

### Brand lockup (signature)
`<x-ui.brand>` — the authentic `eman.png` mark (never redrawn) + "Academy" wordmark; `dark` prop flips text for the navy shell; used on login, rail, checkout, mobile drawer.

## Do's and Don'ts

### Do:
- **Do** open every surface with `.register` label + hairline + `.h1` — the title-page rhythm.
- **Do** use `.ledger` for record lists; hairlines instead of stacked cards.
- **Do** keep gold to stamps: seals, active edges, `.btn-gold` ceremonies.
- **Do** hold icon-only touch targets at ≥44px and text contrast at ≥4.5:1 (`ink-600` minimum for small text, `navy-100` at ≥85% opacity on the shell).
- **Do** use the authentic `public/brand/eman-logo.png` via `<x-ui.brand>`; never redraw the mark.

### Don't:
- **Don't** put navy surfaces inside the content column, or paper panels inside the rail.
- **Don't** add gradients, glows, pill buttons, or shadowed list rows.
- **Don't** introduce a second accent color or a monospace face — folios use letterspaced sans.
- **Don't** use serif below `text-lg` or on interactive controls.
- **Don't** create public marketing pages; the register begins at sign-in.
