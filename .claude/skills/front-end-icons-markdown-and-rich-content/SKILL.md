---
name: front-end-icons-markdown-and-rich-content
description: Use when changing Flare icon usage, RPG Awesome/Font Awesome markup, markdown rendering, Lexical editor behavior, rich content, and user-generated content display.
---

# Flare Icons, Markdown, and Rich Content

Use this skill when changing icons, item/chat visual markers, markdown rendering, Lexical editor features, rich content display, admin content editing, or user-generated content rendering.

## Icon sources

Flare uses icon font classes and RPG Awesome assets.

Current sources include:

```text
@import '../../node_modules/rpg-awesome/css/rpg-awesome.min.css';
```

and Font Awesome-style classes in JSX/Blade patterns such as:

```tsx
<i className="fas fa-times" />
<i className="fas fa-angle-double-right" />
```

Do not add a new icon library unless explicitly requested.

## Icon accessibility

Decorative icons must be hidden:

```tsx
<i className="fas fa-times" aria-hidden="true" />
```

Icon-only buttons must have labels:

```tsx
<button type="button" aria-label="Close panel">
  <i className="fas fa-times" aria-hidden="true" />
</button>
```

Do not rely on icon shape alone to communicate state.

Pair important icons with text or screen-reader text.

## Item and chat colors

Use existing color utility classes for item and chat styling.

Item classes live in:

```text
resources/css/item-colors.css
```

Chat classes live in:

```text
resources/css/chat-colors.css
```

Do not duplicate those colors in JSX.

If an item/chat color has poor contrast, update the utility intentionally.

## Markdown rendering

Flare has `react-markdown` and Lexical markdown packages.

Rules:

- use existing markdown/editor components before creating a new renderer;
- sanitize/limit rendered content according to backend expectations;
- ensure headings, lists, links, and code blocks are readable in light/dark mode;
- links must be keyboard accessible and visually identifiable;
- do not use color alone to mark links;
- avoid rendering raw HTML unless explicitly safe and required.

## Lexical editor

Existing editor code lives under:

```text
resources/js/ui/mark-down-editor
```

Use existing pieces:

```text
mark-down-editor.tsx
tool-bar.tsx
hooks/use-mark-down-paste.ts
plugins/mark-down-paste-plugin.tsx
styles/mark-down-editor-styles.ts
styles/mark-down-editor-theme.ts
```

Rules:

- keep the editor generic;
- pass admin/feature-specific labels and save behavior through props;
- preserve keyboard shortcuts and focus behavior;
- expose validation errors accessibly;
- do not add feature API logic into the shared markdown editor.

## Rich content display

When rendering rich content from the server:

- preserve semantic structure;
- keep text contrast readable;
- ensure images have alt text when meaningful;
- ensure links have clear text;
- avoid opening new windows without indication;
- prevent layout overflow on mobile;
- preserve dark mode prose styles.

## Checklist

An icon/rich-content change is acceptable when:

- existing icon/editor/markdown patterns were reused;
- decorative icons are hidden from screen readers;
- icon-only controls have labels;
- markdown/rich content is readable in light and dark mode;
- mobile overflow is handled;
- user-generated content is not rendered unsafely;
- shared editor components remain generic.
