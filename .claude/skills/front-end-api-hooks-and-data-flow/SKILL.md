---
name: front-end-api-hooks-and-data-flow
description: Use when adding or changing Flare frontend API hooks, API enums, request/response definitions, paginated data, error handling, loading state, or backend contract usage.
---

# Flare Frontend API Hooks and Data Flow

Use this skill when adding, changing, reviewing, or refactoring frontend API calls.

## Core API rule

Components do not call Axios directly.

Components do not build raw API URLs.

Components call typed API hooks.

API hooks use:

```text
api-handler/hooks/use-api-handler
api-handler/utils/get-url
```

The shared API handler adds `/api`, CSRF headers, `Accept: application/json`, and `X-Requested-With: XMLHttpRequest`.

## Shared API infrastructure

The API infrastructure lives under:

```text
resources/js/api-handler
```

Important files:

```text
api-handler/api-handler.tsx
api-handler/components/api-handler-provider.tsx
api-handler/hooks/use-api-handler.ts
api-handler/hooks/use-paginated-api-handler.ts
api-handler/hooks/use-activity-timeout.ts
api-handler/utils/get-url.tsx
api-handler/definitions/paginated-api-response-definition.ts
```

Use the existing API handler. Do not create a second Axios wrapper.

## Feature API folder layout

Feature API code belongs inside the owning feature:

```text
<feature>/api
├── definitions
├── enums
└── hooks
    └── definitions
```

Examples:

```text
resources/js/admin/guide-quests/api
resources/js/game/components/actions/partials/floating-cards/crafting-section/api
resources/js/game/components/side-peeks/map-actions/api
```

## API URL enums

Every feature endpoint belongs in an enum file:

```text
api/enums/<feature>-api-urls.ts
```

Example:

```ts
export enum CraftingApiUrls {
  CRAFT_ITEM = '/character/{character}/crafting/craft-item',
}
```

Rules:

- do not hard-code endpoint strings inside components;
- do not duplicate the same endpoint in multiple files;
- keep route placeholders clear, such as `{character}` or `{guideQuest}`;
- call `getUrl(ApiUrls.SOME_ROUTE, { character: characterId })` inside hooks.

## Request and response definitions

Every request and response shape must have a named definition.

Use:

```text
api/definitions/<action>-request-definition.ts
api/definitions/<action>-response-definition.ts
api/definitions/<entity>-definition.ts
```

Rules:

- keep backend-shaped fields as snake_case;
- do not rename payload fields to camelCase unless the feature already has an explicit mapper;
- avoid `any`;
- do not inline API object shapes inside components;
- do not inline large API object shapes inside hooks;
- define reusable entity shapes once.

Good request shape:

```ts
export default interface CraftItemRequestDefinition {
  item_to_craft: number;
  type: string;
  craft_for_npc: boolean;
  craft_for_event: boolean;
  per_page: number;
  page: number;
  search_text: string;
  filters: Record<string, unknown>;
}
```

## API hook naming

Use action-based hook names:

```text
use-fetch-guide-quest.ts
use-store-guide-quest-content.ts
use-craft-item-api.ts
use-craftable-items-api.ts
use-fetch-location-details-api.ts
```

Hook return definitions live in:

```text
api/hooks/definitions/use-<action>-definition.ts
api/hooks/definitions/use-<action>-params.ts
```

Use explicit names:

```ts
export default interface UseCraftItemApiDefinition {
  isCrafting: boolean;
  error: string | null;
  craftItem: (...) => Promise<void>;
}
```

## Hook behavior

API hooks should own:

- loading state;
- request execution;
- API error parsing;
- API success response storage;
- local success/error messages when the feature needs them;
- request payload construction when the payload is API-specific;
- `useActivityTimeout` handling when the nearby code already uses it or when 401 handling is needed.

API hooks should not own:

- large JSX rendering;
- feature layout;
- screen-manager navigation except narrow callbacks when already established;
- side-peek registration;
- global game-data writes unless the API response intentionally updates global game data.

## Paginated API rules

Use `UsePaginatedApiHandler` for paginated list endpoints.

It already models:

- `data`;
- `loading`;
- `error`;
- `canLoadMore`;
- `isLoadingMore`;
- `page`;
- `response`;
- `setSearchText`;
- `setFilters`;
- `setPage`;
- `setRefresh`;
- `onEndReached`.

Prefer `onEndReached` for infinite loading.

Do not manually increment pages inside UI when the paginated hook already exposes `onEndReached`.

When filters/search/additional params change, reset to page 1.

## Error handling

Use clear user-facing messages.

Do not expose raw stack traces.

Do not swallow API errors when the UI needs to block progress.

Use `ApiErrorAlert` or `Alert` for visible API errors.

When the response is a Laravel validation error, preserve useful field messages when the form can display them.

For generic failures, use specific fallback copy:

```text
Unable to complete the request.
Unable to load guide quest details.
Unable to save guide quest content.
```

Avoid vague copy:

```text
Error
Invalid
Failed
```

## Loading state

Hooks expose loading state with names matching the action:

- `loading`
- `isLoading`
- `isSaving`
- `isCrafting`
- `isLoadingMore`

UI components render accessible loading state.

Loading UI must not rely on color alone and should expose useful text for screen readers.

## Request construction

Build request objects in the API hook or a pure utility.

Use a pure utility when request building is long or reused:

```text
utils/guide-quest-form-data-util.ts
utils/normalize-crafting-type.ts
```

Do not build large request payloads inside JSX.

Do not call `parseInt`, `Number`, or ad hoc normalization repeatedly in component render when a utility can own it.

## Contract mismatch rule

If the backend sends fields in snake_case, keep them snake_case in API definitions.

If the UI needs a different local shape, add an explicit mapper utility with a clear name.

Do not silently mix server snake_case and local camelCase in the same object without a mapper.

## API hook checklist

An API change is acceptable when:

- the endpoint is in a feature API enum;
- request/response shapes are named definitions;
- the component calls a hook, not Axios;
- the hook uses `useApiHandler` and `getUrl`;
- loading and error state are exposed;
- 401/session behavior is handled consistently;
- paginated endpoints use the paginated hook where appropriate;
- backend payload field names remain faithful to the API contract.
