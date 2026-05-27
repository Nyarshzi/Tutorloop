# TutorLoop Mobile Responsiveness Overhaul (TODO)

## Step 1 — Verify hamburger HTML/JS requirements
- [ ] For every listed page, ensure required overlay + sidebar markup exists.
- [ ] Ensure `.menu-btn#menuBtn` exists in `.topbar` and is first child, using three `<span>` lines.
- [ ] Ensure required hamburger JS block exists (exact code).
- [ ] Ensure nav link tap closes sidebar on mobile.

## Step 2 — Update CSS for mobile responsiveness
For each CSS file:
- [ ] Add required hamburger CSS blocks.
- [ ] Add general mobile rules (overflow-x hidden, img max-width, .main padding changes, font sizes).
- [ ] Add responsive grids/cards rules:
  - [ ] Dashboard cards: 4 desktop, 2 tablet/mobile.
  - [ ] Bottom boxes: 3 desktop, 2 tablet, 1 mobile.
  - [ ] Analytics: charts 2 desktop, 1 mobile; stats-cards 4 desktop, 2 mobile.
  - [ ] Messages: desktop side-by-side; mobile stack with contacts max-height 200px and chat 60vh.
  - [ ] Tables: wrap in `.table-wrapper` (verify pages).
  - [ ] Forms: mobile full-width inputs and submit button.
  - [ ] Profile: desktop two-column, mobile single column.
  - [ ] Search results: tutor-grid 3 desktop, 2 tablet, 1 mobile.

## Step 3 — Apply PHP structural fixes (only where needed)
- [ ] Wrap any `<table>` elements in `.table-wrapper` on pages that include tables.

## Step 4 — Testing / Verification
- [ ] Manually test each page at widths: 1024px, 768px, 480px.
- [ ] Confirm hamburger open/close, overlay click, and nav-link close.
- [ ] Confirm cards/bottom/analytics/messages/profile/search responsiveness.

