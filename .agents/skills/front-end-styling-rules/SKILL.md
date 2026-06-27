---
name: front-end-styling-rules
description: Use when adding or changing Flare Tailwind styling, colors, spacing, responsive classes, dark mode, visual variants, and accessibility states.
---

# Flare Frontend Styling Rules

Use this skill for all Tailwind styling in Flare.

## Theme source

Inspect and use:

```text
resources/css/tailwind.css
resources/css/item-colors.css
resources/css/chat-colors.css
resources/css/theme/theme-styles.css
```

Do not invent a styling system beside the existing Tailwind v4 theme.

## Colors

Use project palettes:

```text
primary
brand
danube
gray
rose
emerald
indigo
mango-tango
marigold
wisp-pink
regent-st-blue
artifact-colors
cosmic-colors
item-skill-training
glacier
```

Use item utility classes from `item-colors.css` for item rarity/type colors.

Use chat utility classes from `chat-colors.css` only where chat design expects them.

Do not add raw hex colors in JSX.

## Mobile first

Base classes are mobile.

Use breakpoints only to enhance larger screens:

```text
2xsm, xsm, sm, md, lg, xl, 2xl, 3xl
```

Do not build desktop first.

## Dark mode

Every new/changed visible element must include dark mode where needed:

- text;
- background;
- border;
- placeholder;
- icon color;
- hover;
- focus;
- disabled;
- active/selected;
- success/warning/danger;
- loading/progress.

## Accessibility styling

- Keep visible focus styles.
- Do not rely on hover only.
- Do not use color as the only state indicator.
- Ensure contrast in light and dark mode.
- Disabled states must be visually and programmatically clear.

## Forbidden patterns

Avoid:

- raw hex colors in JSX;
- static inline styles for layout/color;
- unnecessary arbitrary values;
- `calc()`;
- unnecessary `overflow-hidden`;
- hard-coded media queries in components;
- component-specific CSS files unless the existing family already requires one.

Inline style is acceptable for dynamic computed values, such as progress width.

## Styling checklist

A styling change is acceptable when:

- theme tokens are used;
- mobile base layout works;
- dark mode is readable;
- focus states are visible;
- no raw colors/layout hacks were added;
- conditional classes use `clsx` or style helpers;
- accessibility state is not color-only.
