---
name: front-end-styling-rules

description: Use this skill when adding, reviewing, or refactoring Tailwind styling, responsive layouts, colors, spacing, accessibility states, light mode, and dark mode in frontend React/TypeScript code.
---

# Frontend Styling Rules

Use this skill for Tailwind styling in:

resources/js/**
resources/css/tailwind.css

Before styling anything, inspect:

resources/css/tailwind.css

Use the project theme tokens, color palettes, breakpoints, fonts, shadows, radius values, and animation utilities defined there.

Do not invent colors, breakpoints, shadows, or sizing systems when the theme already provides them.

## Theme source of truth

The styling source of truth is:

resources/css/tailwind.css

Use the configured theme values from that file.

Current breakpoint names are defined there, including:

* 2xsm
* xsm
* sm
* md
* lg
* xl
* 2xl
* 3xl

Use these breakpoints exactly.

Do not invent custom breakpoint names.

## Mobile-first layout

All styling must be mobile first and desktop second.

Base classes are mobile.

Responsive classes enhance larger screens.

Correct pattern:

* mobile base classes first;
* then `sm:`;
* then `md:`;
* then `lg:`;
* then `xl:`;
* then `2xl:`;
* then `3xl:`.

Do not write desktop-first layouts.

Do not hide broken mobile layouts behind desktop classes.

Do not assume the UI is only used on desktop.

## Light and dark mode

Every new or changed UI element must support light and dark mode.

Required styling coverage:

* text
* background
* border
* placeholder text
* focus states
* disabled states
* hover states
* selected states
* success states
* warning states
* danger states
* loading states
* progress states

Use `dark:` classes for dark mode.

Do not leave dark mode using light-only colors.

Do not leave placeholder text unreadable in dark mode.

## Accessibility styling

Styling must preserve accessibility.

Do not remove focus outlines unless replacing them with an equally visible focus style.

Interactive controls must have visible focus states.

Hover-only affordances are not enough.

Color must not be the only way to communicate state.

Text contrast must be readable in both light and dark mode.

Disabled states must be visually clear and programmatically correct.

Loading states must not cause layout jumps when avoidable.

## Tailwind usage rules

Use Tailwind utility classes properly.

Use theme colors from `resources/css/tailwind.css`.

Use project palettes such as:

* primary
* danube
* gray
* emerald
* rose
* mango-tango
* marigold
* regent-st-blue
* artifact-colors
* cosmic-colors
* glacier
* brand

Use exact palette names from `tailwind.css`.

Do not create one-off hex colors in JSX.

Do not use inline style for color, spacing, layout, or sizing when Tailwind can do it.

Inline style is allowed only for dynamic values that cannot be represented safely with static Tailwind classes, such as a computed progress bar width.

## Forbidden styling patterns

Do not use `calc()`.

Do not use unnecessary arbitrary values.

Do not use unnecessary `overflow-hidden`.

Do not use desktop-first responsive classes.

Do not use raw hex colors in JSX.

Do not use hard-coded media queries in component files.

Do not use inline styles for static layout.

Do not use custom CSS files for isolated components unless the existing component family already uses them.

Do not introduce new global CSS unless absolutely required.

Do not use color-only state indicators.

## Conditional classes

Use `clsx` for conditional classes.

Keep class logic readable.

Move reusable class groups into a `styles` folder.

Use enum-driven style functions when a component has variants.

Preferred UI style layout:

resources/js/ui/<component-family>/styles
|
+-- <component-name>-base-styles.ts
+-- <component-name>-variant-styles.ts

Feature-specific style helpers belong in the feature’s `styles` folder, not `resources/js/ui`.

## Progress and loading styling

Progress and loading UI must be accessible, mobile first, light/dark friendly, and stable.

Determinate progress bars may use inline width only for the computed percentage.

Clamp progress values between 0 and 100.

Unknown loading progress should use an indeterminate loading component.

Do not create layout shift when replacing alerts with loading bars.

Do not use `calc()` for progress.

Do not rely on color alone; provide readable labels or screen-reader text.

## Spacing and layout

Use Tailwind spacing utilities.

Group related fields with consistent gaps.

Use `gap-*`, `space-y-*`, `p-*`, `px-*`, `py-*`, `m-*`, and `my-*` from Tailwind.

Do not create dense layouts without readable spacing.

Do not hard-code pixel spacing in inline styles.

Use grid or flex based on the existing nearby pattern.

## Final styling checklist

Before finishing styling work, verify:

* `resources/css/tailwind.css` was inspected;
* theme colors were used;
* mobile layout works first;
* desktop layout enhances mobile;
* light mode is readable;
* dark mode is readable;
* focus states are visible;
* screen-reader behavior is preserved;
* no `calc()` was added;
* no unnecessary `overflow-hidden` was added;
* no raw hex colors were added in JSX;
* no inaccessible color-only state was added.
