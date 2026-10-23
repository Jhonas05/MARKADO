# CampusNav visual design reference

**Inspected:** 23 October 2026

**Reference:** <https://campusnav-ai.jhonasangelocanotal-scc.workers.dev/>

**Shipped assets inspected:** `/assets/index-BOx527PX.css` and
`/assets/index-rYEQVvGN.js`

This document records the live CampusNav implementation observed for the
MARKADO design-system migration. It describes verified visual tokens and
patterns; it does not copy CampusNav branding, content, maps, or navigation.

## Verification method

- Retrieved the live HTML and production CSS successfully (HTTP 200).
- Captured and inspected rendered Chromium screenshots at 1440×1200 and
  390×844 in both light and dark system preferences.
- Compared the screenshots with the shipped CSS variables and responsive media
  queries. Values below are taken from those assets or directly observed in the
  rendered captures.

## Typography

CampusNav ships no custom `@font-face` rule. Its verified stacks are:

```css
--font-heading: -apple-system, BlinkMacSystemFont, "SF Pro Display",
  "SF Pro Text", Inter, "Helvetica Neue", Arial, sans-serif;
--font-body: -apple-system, BlinkMacSystemFont, "SF Pro Text",
  "SF Pro Display", Inter, "Helvetica Neue", Arial, sans-serif;
--font-display: -apple-system, BlinkMacSystemFont, "SF Pro Display",
  "SF Pro Text", Inter, "Helvetica Neue", Arial, sans-serif;
--font-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,
  "Liberation Mono", "Courier New", monospace;
```

The rendered hierarchy uses bold, tightly tracked page headings; medium/bold
section labels; normal-weight secondary copy; and compact uppercase navigation
group labels. MARKADO should use the same system stacks and hierarchy rather
than loading the former Lexend/Atkinson display pairing.

## Verified color tokens

The live CSS stores the main palette as RGB triplets.

| Purpose | Light | Dark |
|---|---:|---:|
| Canvas | `rgb(245 245 247)` | `rgb(10 10 10)` |
| Raised canvas | `rgb(255 255 255)` | `rgb(17 17 17)` |
| Surface/card | `rgb(255 255 255)` | `rgb(24 24 24)` |
| Subtle surface | `rgb(250 250 250)` | `rgb(28 28 30)` |
| Fill | `rgb(245 245 247)` | `rgb(34 34 36)` |
| Strong fill | `rgb(237 237 240)` | `rgb(46 46 49)` |
| Border | `rgb(229 229 234)` | `rgb(42 42 45)` |
| Strong border | `rgb(210 210 215)` | `rgb(58 58 61)` |
| Primary text | `rgb(29 29 31)` | `rgb(245 245 247)` |
| Strong text | `rgb(0 0 0)` | `rgb(255 255 255)` |
| Mid text | `rgb(58 58 60)` | `rgb(209 209 214)` |
| Soft text | `rgb(110 110 115)` | `rgb(161 161 166)` |
| Faint text | `rgb(134 134 139)` | `rgb(142 142 147)` |
| Ghost text | `rgb(174 174 178)` | `rgb(99 99 102)` |
| Text on primary | `rgb(255 255 255)` | `rgb(17 17 17)` |
| Overlay | `rgb(0 0 0 / 32%)` | `rgb(0 0 0 / 60%)` |

CampusNav's default primary, destructive, focus, and chart tokens are
monochrome. MARKADO may retain semantic text labels and icons, but the shared
shell, cards, controls, focus states, and navigation should remain grounded in
this neutral palette.

## Radius, border, shadow, and motion

```css
--radius: 0.75rem;
--radius-sm: 8px;
--radius-md: 12px;
--radius-lg: 18px;
--border-width: 1px;

/* light */
--shadow-soft: 0 1px 2px rgb(0 0 0 / 4%),
  0 4px 14px rgb(0 0 0 / 4%);
--shadow-float: 0 4px 12px rgb(0 0 0 / 8%),
  0 24px 60px rgb(0 0 0 / 18%);

/* dark */
--shadow-soft: 0 1px 2px rgb(0 0 0 / 50%),
  0 6px 18px rgb(0 0 0 / 35%);
--shadow-float: 0 4px 12px rgb(0 0 0 / 50%),
  0 24px 60px rgb(0 0 0 / 60%);

--motion-fast: 0.14s;
--motion-normal: 0.22s;
--motion-reveal: 0.4s;
--motion-ease: cubic-bezier(0.22, 1, 0.36, 1);
```

Additional rendered components use 10, 14, 18, 20, 22, and 24px radii plus
full pills. Motion is disabled to near-zero duration under
`prefers-reduced-motion: reduce`. Visible keyboard focus uses a 2px solid
current-ink outline with a 2px offset.

## Spacing and shell measurements

```css
--space-1: 0.25rem;
--space-2: 0.5rem;
--space-3: 0.75rem;
--space-4: 1rem;
--space-6: 1.5rem;
--space-8: 2rem;
--app-header-height: 3.5rem;
--sidebar-width-expanded: 16.5rem;
--sidebar-width-collapsed: 4.75rem;
--app-max-width: 100rem;
--app-readable-width: 76rem;
--app-page-gutter: clamp(1rem, 2vw, 1.5rem);
--app-page-space: clamp(1.5rem, 3vh, 2.5rem);
--app-section-gap: clamp(1rem, 1.6vw, 1.5rem);
--app-card-padding: clamp(1rem, 1.4vw, 1.5rem);
```

At 768px and above the desktop shell activates the sidebar and removes the
mobile header height. At 1024px, page spacing grows and map/content regions may
become sticky. At 1600px, the page gutter and page space become 2.5rem.

## Components observed

### Desktop shell

- Fixed 16.5rem left sidebar with a 1px right border and a separate scrollable
  main canvas.
- Brand at the top, rounded search field, small uppercase navigation groups,
  44px-ish icon/text rows, and a filled neutral active row.
- Appearance segmented control and profile/actions anchored near the sidebar
  bottom.
- Content uses generous white/black space, readable columns, and one visually
  dominant workspace beside supporting cards.

### Mobile shell

- Compact 3.5rem brand header and a fixed four-item bottom navigation.
- No desktop sidebar. High-priority content appears first and fills the width
  with 1rem page gutters.
- Horizontal pill filters may scroll; the layout never depends on hover.
- Large floating/install surfaces respect the bottom navigation safe area.

### Buttons and segmented controls

- Primary actions are near-black on light and near-white on dark, with inverted
  text and 10–12px radii.
- Secondary controls use a surface fill plus 1px neutral border.
- Selected segmented items invert foreground/background; unselected items stay
  transparent or on a subtle surface.
- Disabled states reduce contrast and must remain non-interactive.

### Cards, tables, and forms

- Cards use surface backgrounds, 1px borders, 12–18px radii, and the soft
  shadow only when elevation materially helps.
- Search and form inputs use strong neutral borders, rounded corners, clear
  placeholder contrast, and the same 2px focus treatment.
- Tables should keep neutral row separators and compact headers; MARKADO status
  text must not rely on color alone.

## Theme behavior

The reference uses `light`, `dark`, and `system`. It reads the saved preference
before first paint, adds `.dark` and `data-theme`, updates the browser
`theme-color` (`#FFFFFF` or `#0A0A0A`), and enables motion only when the OS does
not request reduced motion.

MARKADO mirrors this architecture with its own storage key (`markado-theme`),
uses one token layer for Student/Professor/Administrator surfaces, and keeps
assessment-specific content and authorization semantics independent from the
theme.

## MARKADO adaptation boundaries

- Retain MARKADO naming, icons, assessment terminology, role navigation, and
  security/policy messages.
- Do not copy CampusNav's logo, college seal, map artwork, facility content,
  install prompt, or campus-specific information architecture.
- Production-facing Laravel/Inertia pages use the shared tokens first. The old
  standalone prototype remains only as a migration/test fixture until each
  equivalent real route is verified.
