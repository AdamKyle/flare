---
name: front-end-styling-colors-and-tailwind
description: Use when changing Flare Tailwind classes, colors, typography, spacing, dark mode, design tokens, item colors, chat colors, loaders, or visual variants.
---

# Flare Styling, Colors, and Tailwind Rules

Use this skill when adding, changing, or reviewing Tailwind classes, colors, typography, spacing, dark mode, responsive design, animation classes, progress/loading styles, item colors, or visual variants.

## Styling source of truth

The main theme source is:

```text
resources/css/tailwind.css
```

Global imports are wired through:

```text
resources/css/styles.css
```

Additional color utility files:

```text
resources/css/item-colors.css
resources/css/chat-colors.css
resources/css/theme/theme-styles.css
```

Inspect these files before inventing styling.

## Tailwind version

Flare uses Tailwind CSS v4 with CSS theme tokens.

Use theme token classes from `resources/css/tailwind.css`.

Do not create raw hex colors in JSX.

Do not introduce ad hoc CSS variables when the theme already has a token.

## Fonts

The theme uses Lato:

```text
--font-body: "Lato", sans-serif;
--font-heading: "Lato", sans-serif;
```

Use existing font utilities. Do not introduce a new font unless explicitly requested.

## Breakpoints

Use the configured breakpoints exactly:

```text
2xsm: 360px
xsm: 375px
sm: 640px
md: 768px
lg: 1024px
xl: 1280px
2xl: 1536px
3xl: 1920px
```

Mobile-first means base classes are mobile and breakpoint classes enhance larger screens.

Correct:

```text
w-full md:w-1/2 lg:w-1/4
```

Wrong:

```text
lg:w-1/4 md:w-1/2 w-full
```

Keep responsive class order readable: base, `2xsm`, `xsm`, `sm`, `md`, `lg`, `xl`, `2xl`, `3xl`.

## Core palettes

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
black
white
transparent
```

Typical usage:

- default text: `text-gray-900 dark:text-gray-100`;
- secondary text: `text-gray-700 dark:text-gray-300`;
- muted text: `text-gray-500 dark:text-gray-400`;
- default surface: `bg-white dark:bg-gray-800`;
- subtle surface: `bg-gray-100 dark:bg-gray-900`;
- border: `border-gray-200 dark:border-gray-700`;
- primary action: `danube`, `primary`, or existing button variants;
- danger: `rose`;
- success: `emerald`;
- warning/reward: `mango-tango` or `marigold`;
- special item/state styling: `artifact-colors`, `cosmic-colors`, `wisp-pink`, `item-skill-training`.

## Item color utilities

Item styling already exists in:

```text
resources/css/item-colors.css
```

Available utility classes include:

```text
two-enchant
one-enchant
trinket
artifact
regular-item
unique-item
mythic
holy-item
quest-item
usable-item
mythic-unique
```

Use these existing classes for item rarity/type displays instead of duplicating colors in JSX.

## Chat color utilities

Chat color classes exist in:

```text
resources/css/chat-colors.css
```

Current classes include:

```text
ocean-depths
memories-grass
depths-despair
lipstick
fifties-cheeks
sky-clouds
golden-sheen
```

Use these only where the chat/message design expects them.

If a chat color class needs dark-mode or contrast improvement, update the utility intentionally rather than scattering replacements.

## Theme utilities

`resources/css/theme/theme-styles.css` defines sidebar/menu utilities such as:

```text
menu-item
menu-item-active
menu-item-inactive
menu-item-icon-active
menu-item-icon-inactive
menu-dropdown-item
menu-dropdown-item-active
menu-dropdown-item-inactive
menu-dropdown-badge
no-scrollbar
```

Use them for matching sidebar/menu behavior.

Do not recreate sidebar utility stacks in component JSX.

## Loader colors

The theme defines loader variables and animation utilities:

```text
loader-colors
loader-anim
```

Use existing loaders/progress components before creating new animated loaders.

Loading indicators must be accessible and not purely decorative when they communicate state.

## Dark mode rule

Every visible element changed or added must support dark mode.

Cover:

- text;
- background;
- border;
- placeholder text;
- icon color;
- hover state;
- focus state;
- selected/active state;
- disabled state;
- success/warning/danger state;
- loading/progress state.

Do not add light-only Tailwind classes that become unreadable in dark mode.

## Focus and interactive states

Do not remove focus outlines unless replacing them with an equally visible focus style.

Prefer visible focus rings:

```text
focus:outline-none focus:ring-2 focus:ring-danube-500 dark:focus:ring-danube-300
```

Hover-only styling is not enough. Keyboard users need focus styling.

## Forbidden styling patterns

Avoid:

- raw hex colors in JSX;
- static inline styles for color, spacing, or layout;
- unnecessary arbitrary Tailwind values;
- `calc()`;
- unnecessary `overflow-hidden`;
- custom component CSS files for isolated cases;
- hard-coded media queries in component files;
- color-only state indicators;
- desktop-first responsive classes.

Inline style is allowed for dynamic values that Tailwind cannot know at build time, such as a computed progress width.

## Class organization

Use `clsx` for conditional classes.

For reusable class stacks, use styles files:

```text
styles/base-style.ts
styles/variant-style.ts
styles/<component>-styles.ts
```

Prefer enum-driven variants for shared UI components.

Do not put large object literals of class names directly inside JSX when they can be named clearly.

## Styling checklist

A styling change is acceptable when:

- `resources/css/tailwind.css` was used as source of truth;
- project colors/tokens were used;
- mobile classes are base classes;
- responsive classes enhance larger screens;
- dark mode is covered;
- focus states remain visible;
- color is not the only status indicator;
- no raw hex colors were added in JSX;
- no unnecessary arbitrary values, `calc()`, or layout hacks were added.
