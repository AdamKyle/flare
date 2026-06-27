---
name: front-end-forms-validation-and-errors
description: Use when adding or changing Flare frontend forms, admin forms, form wizard flows, validation hooks, validation messages, API errors, scroll/focus behavior, and form submission UX.
---

# Flare Forms, Validation, and Error Handling

Use this skill when creating, changing, reviewing, or refactoring forms, validation, form wizard flows, admin forms, API error handling, and validation focus behavior.

## Core rule

Forms must be typed, accessible, mobile first, and explicit about validation.

Do not scatter validation logic through JSX.

Do not build large request payloads in component markup.

Do not make users guess what failed.

## Existing form patterns

Current form-related areas include:

```text
resources/js/ui/form-wizard
resources/js/admin/guide-quests/form-components
resources/js/admin/guide-quests/hooks
resources/js/admin/guide-quests/utils/guide-quest-form-data-util.ts
resources/js/api-handler/components/api-error-alert.tsx
resources/js/ui/alerts/alert.tsx
resources/js/ui/input/input.tsx
```

Use these as reference patterns.

## Form ownership

Use this split:

- form shell/navigation: component;
- field sections: child components;
- local form state management: feature hook;
- validation: validation hook or feature hook when small;
- request object construction: pure utility or API hook;
- API submission: API hook;
- API error display: `ApiErrorAlert` or `Alert`.

## Admin form layout

Admin forms under `resources/js/admin/<feature>` should use:

```text
form-components
components
hooks
hooks/definitions
api/definitions
api/hooks
api/hooks/definitions
utils
```

For large admin forms, split by domain section:

```text
manage-guide-quests-basic-quest-attributes.tsx
manage-guide-quests-required-stats.tsx
manage-guide-quests-required-currencies.tsx
manage-guide-quests-rewards-and-bonuses.tsx
```

Do not create one massive admin form component with every field inline.

## Validation shape

Prefer a consistent validation result:

```ts
interface ValidationResultDefinition {
  is_valid: boolean;
  form_error: string | null;
  field_errors: Record<string, string>;
}
```

Use feature-specific field error interfaces when fields are known.

Rules:

- validation returns structured data, not just booleans;
- messages are specific;
- field errors are close to fields;
- form-level errors summarize blocking issues;
- validation hooks/utilities have no API calls.

## Validation location

Use a validation hook when validation needs React state, memoization, or several step-specific validators:

```text
hooks/use-<feature>-validation.ts
hooks/definitions/use-<feature>-validation-definition.ts
```

Use a pure utility when validation is deterministic and independent of React:

```text
utils/validate-<feature>.ts
```

Do not put long validation functions inside page/root components.

## Error copy

Use direct, specific messages.

Good:

```text
Enter a guide quest title.
Enter a valid level requirement.
Select at least one quest reward.
Unable to save guide quest content.
You have been logged out due to inactivity. One moment while we redirect you.
```

Bad:

```text
Invalid.
Bad input.
Something went wrong.
Error.
```

## Error display

Use existing shared components:

```text
api-handler/components/api-error-alert.tsx
ui/alerts/alert.tsx
```

Blocking validation errors should use accessible alert behavior.

Field-level errors should be associated with inputs using `aria-describedby` when possible.

Do not communicate errors only by border color.

## Scroll and focus on errors

When validation blocks progress or submit:

- focus the error summary or first invalid field;
- scroll the error into view when it may be below/above the viewport;
- do not steal focus during normal typing;
- do not focus elements during render;
- use `useEffect` or explicit submit/next handlers.

For wizard flows, the current invalid step should remain active.

Do not advance the wizard after failed validation.

## Form wizard rules

The shared form wizard lives in:

```text
resources/js/ui/form-wizard
```

It should remain a generic shell.

Feature-specific wizard state, validation, and API calls must live outside the shared wizard and be passed in through props/callbacks.

Wizard rules:

- validate before moving next;
- do not allow next while loading;
- allow previous without re-validating future fields;
- expose clear loading state;
- expose accessible current step information;
- do not hide errors in inactive animated panels from users without summary.

## Submit behavior

On submit/save:

1. clear stale form-level errors when starting a new attempt;
2. validate locally;
3. focus/scroll to errors if validation fails;
4. build the typed request object;
5. call the API hook;
6. display API error or success state;
7. update local/global state only after success.

Do not optimistically mutate global game state unless the API flow intentionally supports it.

## Numeric and select fields

Numeric form values from inputs are strings until parsed.

Rules:

- parse in validation or request mapping;
- do not use repeated ad hoc `Number(...)` in JSX;
- reject invalid numeric strings explicitly;
- preserve empty string while the user is typing when appropriate;
- avoid converting empty strings to zero accidentally;
- show clear messages for required vs invalid values.

## Form checklist

A form change is acceptable when:

- form sections are split clearly;
- validation is typed and not scattered through JSX;
- errors are specific and accessible;
- field errors are connected to fields when possible;
- failed submit/next keeps the user on the right step;
- focus/scroll behavior helps users find errors;
- request objects are built in utilities/hooks;
- API errors use shared alert components;
- mobile layout and screen-reader behavior work.
