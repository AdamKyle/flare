---
name: front-end-accessibility-and-screen-readers
description: Use for every Flare frontend change to ensure UI is keyboard accessible, screen-reader friendly, semantic, focus-safe, and WCAG-oriented.
---

# Flare Accessibility and Screen Reader Rules

Use this skill for every frontend change. Accessibility is not optional.

All Flare frontend work must be keyboard accessible, screen-reader friendly, semantic, and usable in light and dark mode.

## Core rule

Every new or changed interactive UI must be 100% accessible and screen-reader friendly.

Do not rely on visual styling alone.

Do not ship controls that only work with a mouse.

Do not hide required information inside hover-only UI.

## Semantic HTML first

Use semantic elements before ARIA:

- actions: `<button type="button">`;
- navigation links: `<a href="...">`;
- headings: `<h1>` through `<h6>` in logical order;
- lists: `<ul>`, `<ol>`, `<li>`;
- tables: `<table>`, `<thead>`, `<tbody>`, `<th>`, `<td>` when tabular;
- forms: `<form>`, `<label>`, `<input>`, `<select>`, `<textarea>`;
- dialogs: element with `role="dialog"`, `aria-modal="true"`, title association.

Do not add `role="button"` to a div when a real button works.

## Accessible names

Every interactive control needs an accessible name.

Visible text can be the accessible name.

Icon-only controls need `aria-label`.

Examples:

```tsx
<button type="button" aria-label="Close panel">
  <i className="fas fa-angle-double-right" aria-hidden="true" />
</button>
```

```tsx
<Button label="Craft item" aria_label="Craft selected item" />
```

Do not leave icon-only buttons unnamed.

## Icons

Decorative icons must use:

```tsx
aria-hidden="true"
```

Informational icons must have text or an accessible label.

Do not make screen readers announce icon font class names.

## Forms

Form controls must have labels.

A placeholder is not a label.

Validation errors should be:

- specific;
- near the field;
- connected with `aria-describedby` where possible;
- announced with `role="alert"` or `aria-live` for blocking errors;
- not communicated only through red color.

Required fields should be indicated visually and programmatically.

Disabled fields should use the real `disabled` attribute when they cannot be used.

## Error handling

Blocking errors should be announced.

Use clear copy:

```text
Enter a guide quest title.
Select at least one required faction.
Unable to save guide quest content.
```

Avoid vague copy:

```text
Invalid
Error
Failed
```

When validation blocks form progress, move focus to the error summary or first invalid field where appropriate.

## Loading and status updates

Loading states need screen-reader meaning.

Use one of:

```text
role="status"
aria-live="polite"
aria-busy="true"
```

Progress bars need:

```text
role="progressbar"
aria-valuemin="0"
aria-valuemax="100"
aria-valuenow={value}
```

If loading content replaces important UI, preserve context so screen-reader users understand what is happening.

## Dialogs and side-peeks

Side-peeks behave like dialogs.

They must:

- expose `role="dialog"`;
- expose `aria-modal="true"`;
- have a title via `aria-labelledby` or `aria-label`;
- focus the dialog or first meaningful focusable element when opened;
- close on Escape when close is allowed;
- provide a visible close button;
- prevent background scroll when open;
- not leave keyboard focus trapped behind the overlay;
- restore focus or leave focus in a predictable place when closed.

Do not focus elements during render. Use effects for focus management.

## Keyboard behavior

Every interactive flow must work with keyboard only.

Check:

- Tab order is logical;
- Shift+Tab works;
- Enter/Space activates buttons;
- Escape closes dialogs/side-peeks when supported;
- dropdowns and tabs are keyboard reachable;
- focus is visible;
- disabled controls are not focusable unless using a correct roving/aria pattern.

## Tabs

Tabs should use correct tab semantics when implemented as tabs:

```text
role="tablist"
role="tab"
role="tabpanel"
aria-selected
aria-controls
id
```

If the existing component family has a simpler pattern, improve it without breaking consumers.

## Color and contrast

Color must not be the only way to communicate status.

Pair color with text, icons with labels, or structural changes.

Examples:

- success: green color plus success text;
- danger: red color plus error text;
- selected: active styling plus `aria-selected` or button state;
- progress: visual bar plus label/value.

Ensure contrast works in both light and dark mode.

## Hidden content

Use `aria-hidden` for hidden animated screens/panels that remain in the DOM.

Do not put focusable elements inside `aria-hidden` content unless they are also unfocusable or pointer/keyboard blocked.

For screen-reader-only text, use an existing sr-only pattern or Tailwind `sr-only`.

## Accessibility checklist

A frontend change is acceptable when:

- semantic HTML is used;
- every control has an accessible name;
- icon-only controls are labeled;
- decorative icons are hidden from assistive tech;
- forms have labels and accessible errors;
- keyboard-only operation works;
- focus is visible;
- dialogs/side-peeks manage focus and Escape behavior;
- loading/progress states are announced;
- color is not the only state indicator;
- light and dark contrast are readable.
