---
name: front-end-mobile-first-responsive-layout
description: Use when changing Flare responsive layouts, mobile game shell behavior, bottom navigation, side-peeks, forms, grids, cards, and viewport-specific UI.
---

# Flare Mobile-First Responsive Layout

Use this skill when changing layout, responsive classes, mobile game UI, bottom navigation, side-peeks, forms, grids, cards, panels, or any UI that must work across phone, tablet, and desktop sizes.

## Core rule

Flare frontend is mobile first.

Base Tailwind classes are the mobile layout.

Responsive prefixes enhance the layout as the viewport gets larger.

Do not build desktop first and patch mobile afterward.

## Breakpoint order

Use the project breakpoints in this order:

```text
base
2xsm
xsm
sm
md
lg
xl
2xl
3xl
```

Example:

```tsx
<div className="w-full space-y-4 md:grid md:grid-cols-2 md:gap-4 md:space-y-0 lg:grid-cols-3">
```

## Mobile game shell

The mobile game shell uses the `mobile-shell` class.

Global CSS hides the mobile bottom nav when editable controls are focused:

```css
.mobile-shell:has(:is(input:focus, textarea:focus, [contenteditable="true"]:focus)) .mobile-bottom-nav,
.mobile-shell:has(:is(input:focus, textarea:focus, [contenteditable="true"]:focus)) .mobile-bottom-nav-spacer {
  display: none;
}
```

Rules:

- preserve `mobile-shell` around the game shell;
- preserve `mobile-bottom-nav` and `mobile-bottom-nav-spacer` class behavior where used;
- do not replace this keyboard handling with brittle JS unless explicitly required;
- avoid fixed elements that overlap focused inputs on mobile;
- include safe-area spacing when adding fixed bottom UI.

## Side-peek responsive behavior

The generic side-peek currently uses:

```text
w-full md:w-1/2 lg:w-1/4
```

Meaning:

- mobile: full width;
- medium screens: half width;
- large screens: quarter width.

Rules:

- keep side-peeks full-width on mobile;
- do not make side-peek content require horizontal scrolling;
- ensure close buttons are reachable by keyboard and thumb;
- ensure long side-peek content scrolls inside the panel, not behind it;
- avoid fixed-width content inside side-peeks.

## Forms on mobile

Forms must be usable one-handed and with screen readers.

Rules:

- stack fields by default;
- use grids only from larger breakpoints;
- keep labels visible;
- keep errors directly near fields;
- make touch targets large enough;
- do not place critical actions only in hover menus;
- avoid horizontal scroll;
- keep submit/navigation controls visible and reachable.

Good pattern:

```text
space-y-4 md:grid md:grid-cols-2 md:gap-4 md:space-y-0
```

## Cards and panels

Cards and panels should use:

- full width on mobile;
- readable padding;
- vertical stacking;
- grids/flex rows only on larger screens;
- `min-w-0` where text truncation inside flex/grid children is required;
- stable loading skeleton/loader size to avoid layout jumps.

Do not use hard-coded desktop widths for cards.

## Tables and dense data

Game/admin data can be dense. Mobile users still need access.

Prefer:

- stacked cards for mobile;
- responsive grids;
- horizontally scrollable table containers only when table semantics are required;
- clear row labels;
- accessible table headers when using tables.

Do not hide essential columns on mobile unless there is an alternative way to access the same information.

## Touch targets

Interactive touch targets should be easy to activate.

Use enough padding around:

- icon buttons;
- tabs;
- dropdown options;
- mobile nav actions;
- side-peek close buttons;
- form wizard previous/next buttons;
- item action buttons.

Avoid tiny clickable icons without padding.

## Motion on mobile

Motion must not make mobile interaction feel sluggish.

Rules:

- keep transitions short;
- avoid heavy layout animations on frequently updating game sections;
- set `aria-hidden` and pointer events correctly for hidden animated screens;
- avoid animations that cause content to jump under a user's finger.

## Responsive layout checklist

A layout change is acceptable when:

- mobile base layout works without breakpoint classes;
- larger breakpoints enhance the mobile layout;
- there is no horizontal scroll unless intentional and accessible;
- side-peeks are full-width on mobile;
- forms remain usable with the on-screen keyboard;
- fixed mobile nav does not block focused fields;
- touch targets are large enough;
- dark mode and focus states work at all breakpoints.
