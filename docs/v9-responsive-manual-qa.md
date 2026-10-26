# MARKADO V9 responsive and theme QA checklist

Use only the guarded local demo runtime described in `v9-gamification-demo-runbook.md`. Record the browser/version, operating-system theme, viewport, page, and result for each observation.

## Viewport matrix

Repeat the checks at widths `1920`, `1440`, `1280`, `1024`, `768`, `390`, and `360` pixels. Use a normal desktop height where possible and at least 760 pixels for the mobile widths.

At every width confirm:

- no horizontal page scroll for normal content;
- headings, cards, fields, and actions stay inside the viewport;
- cards use balanced columns without an unexplained empty right side;
- focus rings remain visible and are not clipped by sticky headers or navigation;
- Light has light canvas/surfaces with dark readable labels;
- Dark has dark canvas/surfaces with light readable labels;
- switching Light → Dark → System updates immediately without reloading;
- the selected preference survives route navigation and a reload;
- System follows a live operating-system preference change.

## Public and Student pages

Check Login, Student dashboard, Join, Preflight, active quiz, submission receipt, Character Collection, and Character Shop.

- Login fields, error text, primary action, boundary notice, and logo remain legible in both themes.
- Desktop Student navigation shows Sign out, Collection, Shop, wallet balance, appearance, and account identity.
- At 768 pixels and below, the sticky mobile header exposes a named Sign out control; the four-item bottom navigation is not clipped.
- At 390 and 360 pixels, mobile theme, wallet, and Sign out controls remain keyboard/touch accessible with no overlap.
- DELETE Sign out redirects to Login; browser history cannot restore an authenticated page without a server request.
- Dashboard equipped-character art is secondary to quiz access and identity information.
- Shop price, ownership, balance, disabled insufficient-balance state, and server error are readable without relying only on color.
- Collection clearly distinguishes equipped, owned, and locked characters.
- The active timed quiz does not show wallet/character decoration; both timers, progress, question, choices, save state, and final action remain primary.
- Submission receipt may show `+10 MARKADO Coins` but shows no academic points before Professor release.

## Professor pages

Check quiz listing, Create Quiz, Edit Quiz, and Results.

- On desktop, the sidebar always contains the named Sign out control and signed-in account summary.
- At tablet/mobile widths, the sticky header and opened navigation both provide an accessible Sign out path.
- DELETE Sign out redirects to Login; GET `/logout` does not mutate the session.
- Page content expands into available space on wide displays while forms and text retain readable line lengths.
- Quiz cards, authoring columns, settings, question editors, results table, and page actions align without clipped content or oversized empty columns.
- The mobile navigation drawer overlays content with a backdrop, correct stacking, and a reachable close action.

## Keyboard and accessibility spot checks

- Tab from the browser chrome to Skip link, primary navigation, theme controls, page controls, and Sign out.
- Activate links/buttons with Enter or Space as appropriate.
- Confirm control names with the accessibility inspector: `Sign out of Professor workspace`, `Sign out of Student workspace`, theme choices, wallet balance, purchase, and equip.
- Confirm 200% browser zoom does not create two-dimensional scrolling for ordinary content.
- Confirm reduced-motion preference removes nonessential transition motion.
