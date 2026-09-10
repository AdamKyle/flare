# Phase A — Skill Compliance, Realtime State, Stacking and UI Regression Cleanup

## Files created

Backend:
- `app/Game/Character/CharacterInventory/Services/CharacterActiveBoonService.php`
- `app/Admin/Monsters/Requests/MonsterGemEffectContextIndexRequest.php`
- `app/Info/Requests/MonsterGemEffectContextIndexRequest.php`
- `tests/Unit/Game/Character/CharacterInventory/Services/CharacterActiveBoonServiceTest.php`

Frontend:
- `resources/js/game/components/chat-section/websockets/hooks/definitions/npc-message-payload-definition.ts`
- `resources/js/game/components/chat-section/websockets/hooks/definitions/private-message-payload-definition.ts`
- `resources/js/game/components/chat-section/websockets/hooks/definitions/global-message-payload-definition.ts`
- `resources/js/game/components/quests/quest-detail-screen.tsx`
- `resources/js/game/components/quests/types/quest-detail-screen-props.ts`
- `resources/js/admin/monsters/api/hooks/use-monster-gem-effect-contexts.ts`
- `resources/js/admin/monsters/api/hooks/definitions/use-monster-gem-effect-contexts-definition.ts`
- `resources/js/admin/monsters/api/hooks/definitions/use-monster-gem-effect-contexts-params.ts`
- `resources/js/admin/monsters/screens/monster-gem-effect-context-screen.tsx`
- `resources/js/information/monsters/api/hooks/use-public-monster-gem-effect-contexts.ts`
- `resources/js/information/monsters/api/hooks/definitions/use-public-monster-gem-effect-contexts-definition.ts`
- `resources/js/information/monsters/api/hooks/definitions/use-public-monster-gem-effect-contexts-params.ts`
- `resources/js/game-data/hooks/definitions/use-character-boons-update-stream-response.ts`
- `resources/js/game/components/actions/partials/floating-cards/crafting-section/sections/alchemy/active-boons/api/hooks/use-active-boons-actions.ts`
- `resources/js/game/components/actions/partials/floating-cards/crafting-section/sections/alchemy/active-boons/api/hooks/definitions/use-active-boons-actions-definition.ts`
- `resources/js/game/components/actions/partials/floating-cards/crafting-section/sections/alchemy/active-boons/api/hooks/definitions/use-active-boons-actions-params.ts`

## Files deleted

- `resources/js/game/components/actions/partials/floating-cards/crafting-section/sections/alchemy/active-boons/api/hooks/use-active-boons-api.ts`
- `resources/js/game/components/actions/partials/floating-cards/crafting-section/sections/alchemy/active-boons/api/hooks/definitions/use-active-boons-api-definition.ts`
- `resources/js/game/components/actions/partials/floating-cards/crafting-section/sections/alchemy/active-boons/api/hooks/definitions/use-active-boons-api-params.ts`
- `resources/js/game/components/actions/partials/floating-cards/crafting-section/sections/alchemy/active-boons/api/definitions/active-boons-response-definition.ts` (dead once the eager GET was removed)
- `resources/js/game/components/actions/partials/floating-cards/crafting-section/sections/alchemy/active-boons/websockets/hooks/use-active-boons-websocket.ts`
- `resources/js/game/components/actions/partials/floating-cards/crafting-section/sections/alchemy/active-boons/websockets/hooks/definitions/use-active-boons-websocket-params.ts`
- `resources/js/game/components/actions/partials/floating-cards/crafting-section/sections/alchemy/active-boons/websockets/enums/active-boons-web-socket-channels.ts`
- `resources/js/game/components/actions/partials/floating-cards/crafting-section/sections/alchemy/active-boons/websockets/enums/active-boons-websocket-event-names.ts`

## Files modified

Backend:
- `app/Game/Character/CharacterInventory/Events/CharacterBoonsUpdateBroadcastEvent.php` — inspected only, no change needed (payload contract already `array $boons`).
- `app/Game/Character/CharacterInventory/Services/UseItemService.php` — injected `CharacterActiveBoonService`, `broadcastCharacterBoons()` now dispatches the presentation-ready contract instead of raw `CharacterBoon::toArray()` rows.
- `app/Game/Character/CharacterSheet/Controllers/Api/CharacterSheetController.php` — injected `CharacterActiveBoonService`, deleted private `activeBoonRows()`, removed `UsableItemTransformer` params from `activeBoons`/`cancelBoon`/`fillUpBoon`.
- `app/Game/Character/CharacterSheet/Transformers/CharacterSheetTransformer.php` — injected `CharacterActiveBoonService`, added `active_boons` to the initial sheet contract.
- `app/Game/Monsters/Services/MonsterGemEffectContextService.php` — injected `Pagination`; added `summary()` and `paginate()` built on the existing `forMonster()` cache scan (no Gem math changed).
- `app/Game/Monsters/Services/MonsterReadService.php` — injected `MonsterGemEffectContextService`; added `gemEffectContexts()`.
- `app/Game/Monsters/Transformers/MonsterDetailTransformer.php` — replaced eager `gem_effect_contexts` field with `gem_effect_context_count`/`gem_effect_context_preview`, derived from the explicit gameplay context when supplied, else from the factual summary.
- `app/Admin/Monsters/Controllers/Api/MonstersController.php` — added `gemEffectContexts()` action.
- `app/Info/Controllers/Api/MonstersController.php` — added `gemEffectContexts()` action; removed stale parameter-doc prose while touching the class.
- `routes/admin/monsters/api.php` — added `GET /admin/monsters/{monster}/gem-effect-contexts`.
- `routes/information/api.php` — added `GET /monsters/{monster}/gem-effect-contexts`.

Frontend — Chat:
- `resources/js/game/api-definitions/chat/chat-message-definition.ts` — added `private-message-received` and `npc-message` to `ChatMessageType`.
- `resources/js/game/components/chat-section/components/messages/messages.tsx` — safe view-model builder (`buildChatRowViewModel`), no crash on missing sender, no literal `"null"` rendering, no empty clickable sender target.
- `resources/js/game/components/chat-section/websockets/hooks/use-chat-messages.ts` — now owns all four public/global/npc/private subscriptions and accepts `user_id`.
- `resources/js/game/components/chat-section/websockets/hooks/use-chat-stream.ts` — passes the real `user_id` into `useChatMessages`.
- `resources/js/game/components/chat-section/websockets/hooks/definitions/event-payload-definition.ts` — matches `MessageSentEvent` exactly (`message`, `name`, `nameTag`).
- `resources/js/game/components/chat-section/websockets/hooks/definitions/regular-message-payload-definition.ts` — removed fabricated `name`/`nameTag`.
- `resources/js/game/components/chat-section/websockets/enums/chat-web-socket-channels.ts` — added `GLOBAL_MESSAGE` channel.
- `resources/js/game/components/chat-section/websockets/enums/event-message-types.ts` — reduced to an `as const` map of the four literal event-driven chat types, used by the converters (no more casts).
- `resources/js/game/components/chat-section/websockets/utils/to-chat-type-from-event.ts` — rewritten as four explicit converters (`toChatTypeFromPublicMessage/NpcMessage/PrivateMessage/GlobalMessage`), each matching its real backend payload.

Frontend — Class/DetailGrid/Progress dark mode:
- `resources/js/ui/progress/progress-bar.tsx` — label row now neutral `text-gray-700 dark:text-gray-200` instead of semantic card color.
- `resources/js/ui/progress/styles/progress-bar/card-variant-styles.ts` — corrected dark-mode surface/text for PRIMARY, SUMMER, PINK_MOON, ARTIC, DE_YORK (deep surface + light text, no opacity shortcuts).
- `resources/js/ui/detail-grid/detail-grid.tsx`, `detail-grid-row.tsx`, and their `types/*-props.ts` — added `single_column` prop.
- `resources/js/game/reusable-components/class/components/class-detail.tsx`, `types/class-detail-props.ts` — threaded `single_column` into `DetailGrid`.
- `resources/js/game/reusable-components/class/components/class-prerequisite-card.tsx` — fixed dark surface/text (danube-900 dark surface + danube-100/200 text instead of light-100 surface + dark text).
- `resources/js/game/components/side-peeks/game-data/class-detail-side-peek.tsx` — passes `single_column`.
- `resources/js/game/components/character-sheet/class-ranks/components/class-rank-detail-content.tsx` — passes `single_column` (covers both `CharacterClassRankDetailSidePeek` and `ClassRankDetailStack`, its only two callers).

Frontend — Quest ScreenManager:
- `resources/js/configuration/screen-manager/screen-manager-constants.ts`, `screen-manager-props.ts`, `screen-manager-registry.ts` — added `QUEST_DETAIL` screen.
- `resources/js/game/components/character-sheet/character-quests.tsx` — removed local `openQuestId` state and the nested `CharacterQuestDetailStack`; pushes `Screens.QUEST_DETAIL` instead.
- `resources/js/game/components/quests/quest-log-screen.tsx` — inspected only, no change needed.

Frontend — Monster Gem-effect browsing:
- `resources/js/game/reusable-components/monster/api/definitions/monster-detail-definition.ts` — `gem_effect_context_count`/`gem_effect_context_preview` replace `gem_effect_contexts`.
- `resources/js/game/reusable-components/monster/components/monster-detail.tsx` — single-context tab renders `MonsterGemEffectContextCard` directly from the preview; multi-context tab is fed the caller-owned paginated browser state; no local ScreenManager/StackedCard decision inside this shared component.
- `resources/js/game/reusable-components/monster/components/monster-special-location-effects-tab-panel.tsx` — removed local pseudo-pagination (`PAGE_SIZE`, `SCROLL_LOAD_THRESHOLD_PX`, `visibleCount`); now a pure append-paginated list using `InfiniteScroll` with a real `flex flex-col gap-2` child wrapper and `InfiniteLoader` while `loading_more`.
- `resources/js/game/reusable-components/monster/components/monster-gem-effect-context-card.tsx` — changed-value grid now uses `DetailGrid` with a `single_column` prop instead of a hardcoded `md:grid-cols-2`.
- `resources/js/game/reusable-components/monster/types/monster-detail-props.ts`, `monster-special-location-effects-tab-panel-props.ts`, `monster-gem-effect-context-card-props.ts` — updated contracts.
- `resources/js/admin/monsters/api/enums/monster-api-urls.ts` — added `GEM_EFFECT_CONTEXTS`.
- `resources/js/admin/monsters/screens/monster-show-screen.tsx` — uses the new paginated hook, pushes `MonsterScreens.GEM_EFFECT_CONTEXT` on selection.
- `resources/js/admin/monsters/components/side-peeks/admin-monster-detail-side-peek.tsx` — extended local `nestedSelection` with `gem_effect_context`, rendered in the existing local `StackedCard`.
- `resources/js/admin/monsters/components/types/monster-nested-selection.ts` — added the `gem_effect_context` selection variant.
- `resources/js/admin/monsters/screen-manager/monster-screen-constants.ts`, `monster-screen-props.ts`, `monster-screen-registry.ts` — added `GEM_EFFECT_CONTEXT` screen.
- `resources/js/information/monsters/api/enums/monster-info-api-urls.ts` — added `GEM_EFFECT_CONTEXTS`.
- `resources/js/information/monsters/monsters-info-app.tsx` — uses the new public paginated hook; selecting a context swaps local state to render `MonsterGemEffectContextCard` as the factual detail target (no fake screen system).
- `resources/js/game/components/actions/partials/monster-stat-section/monster-stat-section.tsx` — `initial_context_tab` now reads `gem_effect_context_count` (gameplay current-context semantics unchanged).

Frontend — Global Active Boon state:
- `resources/js/game-data/api-data-definitions/character/character-sheet-definition.ts` — added `active_boons: ActiveBoonDefinition[]` (reused the existing definition).
- `resources/js/game-data/components/game-data-provider.tsx` — added `handleOnBoonsUpdate`, wired into `useCharacterUpdates`.
- `resources/js/game-data/components/character-updates-wire.tsx` — subscribes to `update-boons-{userId}` / `CharacterBoonsUpdateBroadcastEvent`.
- `resources/js/game-data/components/event-enums/core-web-socket-channels.ts`, `core-web-socket-event-names.ts` — added the boon channel/event.
- `resources/js/game-data/components/types/character-update-wire-props.ts`, `resources/js/game-data/hooks/definitions/use-character-update-params-definition.ts`, `resources/js/game-data/hooks/use-character-updates.tsx` — threaded `onBoonsEvent` through.
- `resources/js/game/components/actions/partials/icon-section/hooks/use-character-active-boon-status.ts` — pure read of `gameData.character.active_boons`, no GET, no local websocket.
- `resources/js/game/components/actions/partials/floating-cards/character-details/character-card.tsx` — reads `active_boons` from global state; uses the renamed action hook for fill/remove only.
- `resources/js/game/components/actions/partials/floating-cards/character-details/character-card-details.tsx`, `types/character-card-details-props.ts` — removed `active_boons_loading`/`on_active_boons_complete`.
- `resources/js/game/components/actions/partials/floating-cards/character-details/alchemy-boons/character-alchemy-boons.tsx`, `types/character-alchemy-boons-props.ts` — removed the now-unused `loading`/`on_complete` props.
- `resources/js/game/components/actions/partials/floating-cards/crafting-section/sections/alchemy/active-boons/components/active-boons.tsx` — reads the list from global state, uses the renamed action hook.
- `resources/js/game/components/side-peeks/character-inventory/usable-items/usable-items.tsx` — a third, previously unlisted GET+local-websocket consumer of the old hook, discovered while deleting its dependencies; converted to read `gameData.character.active_boons` the same way.

Backend tests:
- `tests/Feature/Game/Character/CharacterSheet/Controllers/Api/CharacterSheetControllerTest.php` — existing active-boon endpoint tests already matched the new service's contract unchanged; added `test_character_sheet_includes_active_boons_using_the_shared_presentation_contract`.
- `tests/Unit/Game/Character/CharacterSheet/Transformers/CharacterSheetTransformerTest.php` — added `active_boons` to the expected-key contract test; added `test_transform_includes_active_boons_using_the_real_active_boon_data`.
- `tests/Unit/Game/Character/CharacterInventory/Services/UseItemServiceTest.php` — added `test_using_an_item_broadcasts_the_presentation_ready_active_boon_contract` using `Event::fake()` + `assertDispatched($class, $callback)` to inspect the real payload (not a bare class-name assertion), executing the real item-use path.
- `tests/Unit/Game/Monsters/Services/MonsterGemEffectContextServiceTest.php` — added summary, pagination (page 1/page 2/`can_load_more`), and raid-exclusion tests, all built on manually-seeded `Cache` rows matching the existing test's established fixture technique (no real Gem math recalculated).
- `tests/Feature/Admin/Monsters/MonstersApiControllerTest.php` — replaced the eager `gem_effect_contexts` assertions with `gem_effect_context_count`/`gem_effect_context_preview` (+ `assertArrayNotHasKey`); added endpoint tests for pagination shape/page-size, raid exclusion, and admin/guest authorization on the new route.
- `tests/Feature/Info/Monsters/MonstersApiControllerTest.php` — same compact-summary replacement; added a guest-readable pagination test for the new route.

## Skill/convention violations removed

- Fabricated top-level websocket `event.type` and fabricated `name`/`nameTag` fields on the regular message payload (chat).
- `"null"` string rendering and crash-prone `.trim()` on a possibly-absent `character_name`.
- Duplicate/competing Active Boon ownership: three independent GET-on-mount + feature-local-websocket consumers (`CharacterCard`, the icon-status hook, the crafting-section `ActiveBoons`, and a third undocumented one in `usable-items.tsx`) collapsed into one global, websocket-synchronized source of truth.
- Raw `CharacterBoon::toArray()` broadcast payload replaced with the presentation-ready contract.
- Quest Log's nested full-bleed `StackedCard`-inside-`StackedCard` screen-inside-screen pattern replaced with proper ScreenManager stacking.
- Monster factual detail's eager all-context `gem_effect_contexts` array replaced with a compact count/preview summary; client-side `.slice()` pseudo-pagination replaced with real API-backed append pagination; `InfiniteScroll`'s dead `additional_css` gap class replaced with a real child wrapper.
- Hardcoded `md:grid-cols-2` grids in `DetailGrid`/`MonsterGemEffectContextCard` made SidePeek-aware via `single_column`.
- Dark-mode "light surface + dark text" defect in `card-variant-styles.ts` and `class-prerequisite-card.tsx`.
- Constructor docblocks restating only `@param Type $var` for simple promoted properties are stripped by this repository's actual Pint configuration (confirmed against pre-existing un-annotated constructors such as `AdminGemRollService`); accepted Pint's output rather than fighting it.

## Exact existing behavior intentionally preserved

- Public chat's persisted-message fields (`map_name`, `custom_class`, `is_chat_bold`, `is_chat_italic`, etc.) and the existing `private-message-sent`/local system-chat behavior.
- `MonsterGemEffectContextService::forMonster()`'s cache-scanning, deduplication, and sort algorithm — untouched; `summary()`/`paginate()` are thin wrappers.
- Gameplay `MonsterStatsService`/`MonsterStatSection`'s "Character's current effective context only" semantics.
- Alchemy 8-hour cap, 10-boon max, use-one/use-many/use-all behavior, Alchemy Bag consumption, and every existing `broadcastCharacterBoons()` call site.
- `CharacterQuestDetailStack`'s valid SidePeek-local use from `quest-item-detail-stack.tsx` and `ClassRankDetailStack`'s local StackedCard nesting.
- Admin/full Class show pages remain responsive two-column (`single_column` not passed there).
- `CountdownProgressButton` itself unchanged (its `on_complete` was already optional).

## Excluded areas not touched

- Gem World generation/traversal, Gem-entry UI, Gem mathematics, `AreaGemEffectService`.
- Monster/Character/Quest cache generation code and commands (tests call the pre-existing `BuildMonsterCacheService` methods exactly as the untouched parts of those test files already did; no cache-generation command was run).
- `app/Flare/GameImporter/Console/Commands/MassImportCustomData.php` (left exactly as found — pre-existing uncommitted change, not authored by this task).

## Commands run and results

- `php -d memory_limit=-1 -d max_execution_time=0 vendor/bin/phpunit --filter='CharacterActiveBoonServiceTest|CharacterSheetControllerTest|CharacterSheetTransformerTest|UseItemServiceTest|MonsterGemEffectContextServiceTest|MonstersApiControllerTest'` — **failed to boot**: `Pest must be run through its own binary. Please run [./vendor/bin/pest] instead.` PHPUnit's suite loader eagerly requires every file matched by the configured testsuite globs (including unrelated Pest-only files such as `tests/Feature/Broadcasting/BroadcastAuthorizationTest.php`) before applying `--filter`, so the plain `phpunit` binary cannot run in this repository regardless of filter. This is a pre-existing repository/tooling fact, not caused by this task.
- `php -d memory_limit=-1 -d max_execution_time=0 vendor/bin/pest --filter='CharacterActiveBoonServiceTest|CharacterSheetControllerTest|CharacterSheetTransformerTest|UseItemServiceTest|MonsterGemEffectContextServiceTest|MonstersApiControllerTest'` (identical filter, run through the repository's actual test binary) — **passed: 128 passed (452 assertions)**, 0 failures.
- `./vendor/bin/pint --test` — **passed** (no formatting violations across the repository).
- `yarn lint` — **passed** after fixing 3 auto-fixable errors and closing several `import/order` warnings in touched files; final run shows only 7 pre-existing warnings in files this task never touched.
- `yarn type-check` — **passed**, 0 errors.
- `yarn cleanup` — **passed**, ran prettier across the repo; no files outside this task's own edits were changed; re-ran `yarn lint`/`yarn type-check` afterward per the quality-gate rule (both still pass).
- `yarn unused-files-check` — **passed**: "There don't seem to be any unimported files."
- `yarn build:dev` — **passed**, Vite build completed successfully (3469 modules transformed).
- Mandatory prohibited-pattern audit over every changed/created PHP application and test file: no `declare(strict_types=1)`, no `final class`, no new manual scalar casts beyond one precedented `(int)` cast on an `AlchemyBagSlot::sum('amount')` aggregate (matching the exact existing pattern already used twice in `App\Flare\Models\Character` for the identical query shape), no debug output, no `resolve()`/`app()` in application code, no reflection/non-lifecycle test helpers/direct model factories/manual job `handle()` calls in any touched test file.

## Final status

I am done

# Phase A — Phase 2 Gem World Bootstrap and Cache Pipeline

## Files created

- `app/Console/AfterDevelopment/CreateGemWorlds.php`

## Files modified

- `app/Admin/Services/AdminGemRollService.php`
- `app/Flare/GemWorldGeneration/Services/GemWorldGenerationService.php`
- `app/Providers/AppServiceProvider.php`
- `app/Flare/GameImporter/Console/Commands/MassImportCustomData.php`
- `tests/Unit/Admin/Services/AdminGemRollServiceTest.php`
- `tests/Unit/Flare/GemWorldGeneration/Services/GemWorldGenerationServiceTest.php`

## Files deleted

None.

## `create:gem-worlds` behavior

New command at `app/Console/AfterDevelopment/CreateGemWorlds.php`, namespace `App\Console\AfterDevelopment`, signature `create:gem-worlds`, non-interactive (no `choice()`, no arguments/options). Constructor injects `AdminGemRollService`, `GemWorldGenerationService`, `BuildMonsterCacheService`.

- Loads every `GameMapGemParamter` with `gameMap`, `generatedMap`, `rolledGem`, ordered by `name` then `id`; loads every `GameLocationGemParamter` with `location.map`, `generatedMap`, `rolledGem`, ordered the same way. Every returned profile is processed.
- For each profile: if `rolled_gem_id` is null, rolls via `AdminGemRollService::rollMapGem($profile, null)` / `rollLocationGem($profile, null)`, increments the created counter, prints `Rolled Map/Location Gem: {label}` via `info()`, then refreshes the profile with `fresh([...])` reloading the relationships `GemWorldGenerationService` needs. If `rolled_gem_id` is already set, the profile is left untouched (never rerolled), the skipped counter increments, and `Skipped Map/Location Gem roll; active roll already exists: {label}` is printed via `line()`.
- Regardless of roll/skip, the (possibly refreshed) profile is always passed to `GemWorldGenerationService::generateMapGem()` / `generateLocationGem()`. The returned `GemWorldGenerationResult` is collected and its existing `message` is printed via `info()` (generated), `line()` (skipped), or `error()` (failed) based on the result's own `generated()`/`skipped()`/`failed()` methods — no new status enum or string comparison was introduced.
- Labels: Map Gem = `{gameMap->name} - {profile->name}`; Location Gem = `{location->nameWithPlaneForLocationGem} - {profile->name}`.

## System-roll behavior

`AdminGemRollService::rollMapGem()` and `rollLocationGem()` now accept `?User $admin = null` (was a required `User $admin`); the shared private `roll()` method's `$admin` parameter is now `?User`. `rolled_by_user_id` is persisted as `$admin?->id`. `create:gem-worlds` always passes `null`, so system/bootstrap rolls persist `rolled_by_user_id = null`. No arbitrary Admin user is queried, hardcoded, or created. `MapGemService::roll()`, `MapGemService::rollAll()`, `LocationGemService::roll()`, and `LocationGemService::rollAll()` were not touched and continue passing their real authenticated `User`, so Admin-created rolls continue recording the real Admin User ID exactly as before. No Gem math, roll ranges, random-number behavior, domain values, or profile foreign keys were changed.

## Existing-roll skip behavior

A profile with a non-null `rolled_gem_id` is never rerolled by `create:gem-worlds` — the existing active Gem/roll is preserved and the profile is passed to generation unchanged (aside from its already-loaded relationships).

## Generated-world recovery / tile-generation behavior

In both `GemWorldGenerationService::generateMapGem()` and `generateLocationGem()`, when `$gemParamter->generatedMap` already exists, the service now calls `$this->mapTileGenerationService->tile($generatedMap);` immediately after assigning `$generatedMap`, before deciding whether to retry missing Location placement or return the existing-map `skipped` result. `MapTileGenerationService::tile()` was not modified — its existing idempotency (return early when the tile directory exists and `tile_map` is populated; repair partial output; generate when absent) is reused as-is. No second tile check was added, no directory inspection was added to `GemWorldGenerationService`, and first-time generation still tiles exactly once through the existing `generate()` path (the existing-map tile call and the new-map `generate()` tile call are mutually exclusive branches, so a map is never tiled twice).

## Monster cache invalidation behavior

`create:gem-worlds` tracks the total of newly created Map + Location rolls. If that total is `0`, `BuildMonsterCacheService::invalidateGemAffectedCaches()` is not called. If it is `1` or more, `invalidateGemAffectedCaches()` is called exactly once after all profiles are processed. The command never calls `buildAll()`/`buildCache()` — it does not build Monster caches itself; that remains owned by `generate:monster-cache`. Raid, Weekly, and Celestial caches are not touched by `invalidateGemAffectedCaches()` (unchanged existing implementation).

## `MassImportCustomData` execution order

Inside the existing `config('app.env') !== 'production'` guard in `handle()`, immediately after the existing `$this->importGameMaps();` call, four `Artisan::call()` lines were added in this exact order:

1. `create:gem-worlds`
2. `create:character-attack-data`
3. `generate:monster-cache`
4. `create:quest-cache`

Nothing else in `handle()` was changed: the pre-existing `Artisan::call('import:game-data ...')` lines, `remove:racial-stat-bonuses`, the commented `importInformationSection()` call, `importGameMaps()` itself, and the production guard were left exactly as found in the working tree (the three `import:game-data` calls were already uncommented/active in the working tree before this task started — that pre-existing uncommitted state was not touched or reverted). `importInformationSection()` and `importGameMaps()` were not modified.

## Character cache command wiring

`create:character-attack-data` is invoked via `Artisan::call()` from the new sequence. `CreateCharacterAttackDataCache` itself was not modified: it still dispatches `CreateCharacterAttackData::dispatch($character->id)->onConnection('long_running')` per character (via `chunkById`), so queued dispatch onto `long_running` and chunk size are unchanged. No job was called directly or made synchronous.

## Monster cache command wiring

`generate:monster-cache` (`App\Console\AfterDeployment\CreateMonsterCache`, calling `BuildMonsterCacheService::buildAll()`) is invoked via `Artisan::call()` after `create:gem-worlds` and `create:character-attack-data`, and before `create:quest-cache`. `CreateMonsterCache` and `BuildMonsterCacheService` were not modified.

## Quest cache command wiring

`create:quest-cache` (`App\Game\Quests\Console\Commands\CreateQuestCache`) is invoked last via `Artisan::call()`, after `generate:monster-cache`. `CreateQuestCache` and `BuildQuestCacheService` were not modified.

## Existing Gem-effect behavior preserved

`AreaGemEffectService`, `BuildMonsterCacheService`'s Gem-affected cache building/transformer resolution, `MonsterTransformer`, Character builders (`CharacterAttackBuilder`, `CharacterStatBuilder`, `StatModifierDetails`), Raid/Weekly/Celestial/Cave-of-Memories Gem-neutral exclusions, `generated_map_type`, `generated_parent_game_map_id`, `game_map_gem_paramter_id`, `game_location_gem_paramter_id`, and `can_traverse = false` were not touched. No second Gem rolling engine, Gem World generation engine, or map tiling implementation was created.

## Areas intentionally excluded

- No frontend file was touched.
- No migration was created or modified.
- No operational Artisan command (`create:gem-worlds`, `mass:import-game-data`, `create:gem-worlds`, `create:character-attack-data`, `generate:monster-cache`, `create:quest-cache`, `import:game-data`) was executed.
- `mysql`, `create:gem-worlds`, `create:character-attack-data`, `generate:monster-cache`, `create:quest-cache`, `import:game-data`, and `mass:import-game-data` were not run.
- No command test was created for `CreateGemWorlds` or `MassImportCustomData`.
- Gem-effect mathematics, Monster-effect precedence, Character Gem-effect calculations, and Quest cache behavior were not altered.

## Exact tests changed

`tests/Unit/Admin/Services/AdminGemRollServiceTest.php`: kept every existing test unchanged; added `test_roll_map_gem_with_no_admin_persists_a_system_roll` (proves `rollMapGem($profile, null)` persists a real Gem with `domain = Gem::DOMAIN_MAP`, `rolled_by_user_id = null`, `profile->rolled_gem_id` pointing at the new Gem, `profile->roll_count = 1`) and `test_roll_location_gem_with_no_admin_persists_a_system_roll` (equivalent Location-domain assertions). Both use the existing `RandomNumberGenerator` Mockery pattern already used by the file; no helper methods or data providers were added.

`tests/Unit/Flare/GemWorldGeneration/Services/GemWorldGenerationServiceTest.php`: updated the existing-generated-Map expectations —
- `test_generate_map_gem_is_skipped_when_locations_already_exist` and `test_generate_location_gem_is_skipped_when_locations_already_exist` now expect `MapTileGenerationService::tile()` once (previously unmocked/unexpected).
- `test_generate_map_gem_retries_placements_when_the_map_exists_without_locations`, `test_generate_location_gem_retries_placements_when_the_map_exists_without_locations`, and `test_retry_placements_returns_a_failed_result_when_placement_fails` now expect `tile()` once (previously `shouldNotReceive('tile')`), since the existing-map path now tiles before deciding retry-vs-skip.
- First-time generation tests (`test_generate_map_gem_generates_a_new_map_tiles_it_and_places_locations`, `test_generate_location_gem_generates_a_new_map`, `test_generate_map_gems_maps_over_the_collection`, `test_generate_location_gems_maps_over_the_collection`, `test_create_location_from_template_maps_special_and_delve_location_types`, `test_create_locations_skips_placements_with_no_remaining_template`) were left unchanged; their existing `shouldReceive('tile')->once()` expectations already prove first-time generation tiles exactly once through `generate()` and is not double-tiled.
No test file/fixture trait was created; both files continue using the existing `CreateGameMapGemParamter`/`CreateGameLocationGemParamter`/`CreateGameMap`/`CreateLocation`/`CreateLocationTemplate` traits and `RefreshDatabase`.

## Exact commands run and factual results

- `./vendor/bin/pint app/Console/AfterDevelopment/CreateGemWorlds.php app/Admin/Services/AdminGemRollService.php app/Flare/GemWorldGeneration/Services/GemWorldGenerationService.php app/Providers/AppServiceProvider.php app/Flare/GameImporter/Console/Commands/MassImportCustomData.php tests/Unit/Admin/Services/AdminGemRollServiceTest.php tests/Unit/Flare/GemWorldGeneration/Services/GemWorldGenerationServiceTest.php` — result: `fixed` on `CreateGemWorlds.php` only (Pint removed redundant `@param`/`@return` PHPDoc tags on the new class, matching this repository's actual enforced constructor/method docblock convention, confirmed against `MapGemService`/`LocationGemService`, which carry no `@param`/`@return` tags either); the other six files needed no changes.
- `php -d memory_limit=-1 -d max_execution_time=0 vendor/bin/phpunit --filter='AdminGemRollServiceTest|GemWorldGenerationServiceTest' tests/Unit/Admin/Services/AdminGemRollServiceTest.php tests/Unit/Flare/GemWorldGeneration/Services/GemWorldGenerationServiceTest.php` — result: `OK (26 tests, 133 assertions)`. (The bare `--filter` command without explicit file paths could not boot: PHPUnit's configured `Unit`/`Feature`/`Console` test suites load every test file up front before filtering, and an unrelated pre-existing Pest test, `tests/Feature/Broadcasting/BroadcastAuthorizationTest.php`, fails to load outside the Pest binary — `Pest must be run through its own binary`. This is a pre-existing repository/environment condition unrelated to this task's changes; pointing PHPUnit directly at the two target test files avoids loading the rest of the suite and reaches the same two classes.)
- A manual prohibited-pattern grep audit was run per-file over all seven touched/created files for `final class`, `declare(strict_types`, manual scalar casts, debug output (`dd(`, `dump(`, `var_dump`, `print_r`, `fwrite(STDOUT/STDERR`, `ray(`), and `resolve(`/`app(`: zero matches. A separate grep over both touched test files for `Reflection`/`setAccessible`/`invokeArgs` and `::factory()`: zero matches.

## Confirmation

No operational Artisan command was run. No Gem World generation, image generation, tile generation, or cache build was executed or claimed. The Mass Import pipeline (`mass:import-game-data`) was not tested or executed.

## Final status

I am done

# Phase A — Phase 3 Player Gem World Context, Entry and Exit

## Backend files created

- `app/Game/Gems/Transformers/RolledGemTransformer.php` — shared factual rolled-Gem transformation, moved verbatim (field-for-field) out of `AdminGemRollTransformer`.
- `app/Game/Maps/Transformers/GemWorldContextTransformer.php` — translates a resolved `ResolvedAreaGemEffects` into the Player-facing Gem World context contract (`type`, `label`, `game_map`, `location`, `rules`, `sources` (each appended with its concrete `rolled_gem` via `RolledGemTransformer`), `character_power_reduction`, `monster_effects`, `reward_effects`, `crafting_skill_bonuses`, `rarity_effects`), including the backend-authored explanatory `rules` strings for all four closed context types (`map`, `location`, `map_gem_world`, `location_gem_world`).
- `app/Game/Maps/Services/GemWorldService.php` — sole backend authority for the Map card's Gem action: `context()`, `enter()`, `exit()`. Uses `ChecksAutomationRestrictions` (`AutomationRestrictionService::TRAVERSE`) and `ResponseBuilder`. Resolves the single contextually valid entry (Location Gem wins over Map Gem when standing exactly on a Location; standing on any Location without an eligible Location Gem blocks the Map Gem fallback entirely) by loading the current normal Game Map/Location's Gem profile with `rolledGem`/`generatedMap` and checking the exact `generated_map_type`/`game_map_gem_paramter_id`/`game_location_gem_paramter_id`/`generated_parent_game_map_id` linkage described in the task. Calls `TraverseService::travel()` directly for both entry and exit (never `MovementService::updateCharacterPlane()`), never accepts a client-supplied destination Map id.
- `app/Game/Maps/Controllers/Api/GemWorldController.php` — thin controller matching `MapController`'s existing movement-controller pattern exactly (`is.character.dead` middleware except `context`; `can_move` 422 guard on `enter`/`exit`; extract/unset `status` from the service result).
- `tests/Feature/Game/Maps/Controllers/Api/GemWorldControllerTest.php` — 15 focused tests (see below).
- `tests/Unit/Game/Gems/Transformers/RolledGemTransformerTest.php` — 3 focused tests (see below).

## Backend files modified

- `app/Admin/Transformers/AdminGemRollTransformer.php` — now injects `RolledGemTransformer` and delegates `transform()` to it unchanged; its own public method/contract is untouched, so every existing Admin endpoint response is byte-for-byte identical. No test directly instantiated this class with `new`, so the added constructor dependency required no other changes (Laravel zero-config resolution).
- `app/Game/Gems/Values/ResolvedAreaGemEffects.php` — added `craftingSkillBonuses(): array` returning the already-resolved internal array unchanged (no recalculation).
- `app/Game/Gems/Values/ResolvedAreaGemRarityEffects.php` — added `toArray(): array` returning `unique`/`mythic`/`cosmic` from the existing resolved values.
- `app/Game/Maps/Services/TraverseService.php`:
  - `canTravel()` now rejects immediately (before calling `mapType()`) when `$gameMap->isGeneratedGemMap()` or `! $gameMap->can_traverse`, closing the forged-request hole where a generated child Map would otherwise inherit its parent's plane type through `effectiveGameMap()`.
  - `travel()`'s special parent-plane narrative/global-message block (Shadow Plane, Hell, Purgatory, The Ice Plane, Twisted Memories, Delusional Memories) was extracted into a new private `sendPlaneNarrativeMessages()` method and is now called only when `! $gameMap->isGeneratedGemMap()`. The generic `You have traveled to: {name}` message and `UpdateCharacterStatus` event are unchanged and still fire for every destination, generated or not. `mapType()` itself was not changed, and inherited Gem World domain behavior used elsewhere was not touched.
  - Added missing `@param`/`@return` PHPDoc to `canTravel()` and `travel()` since their bodies were directly touched (clean-as-you-go); no other pre-existing undocumented method in this large file was touched.
- `routes/game/maps/api.php` — added `GET /map/gem-world/{character}` (outside `throttle:moving`, since it is not a movement action) and, inside the existing `throttle:moving` + `is.character.exploring` movement group, `POST /map/gem-world/enter/{character}` and `POST /map/gem-world/exit/{character}`. No existing route/middleware grouping was changed.

## Shared rolled-Gem transformer extraction

`RolledGemTransformer::transform(Gem $gem, bool $isActive): array` is now the single production implementation of the factual rolled-Gem field list (all 33 fields plus `crafting_skills`, `is_active`). `AdminGemRollTransformer` and `GemWorldContextTransformer` both delegate to it; the field list is duplicated in zero production transformers.

## Resolved-effect read-model additions

`ResolvedAreaGemEffects::craftingSkillBonuses()` and `ResolvedAreaGemRarityEffects::toArray()` are pure read accessors over already-computed values; `AreaGemEffectService`'s Gem math was not touched.

## Gem World context response contract

`GemWorldContextTransformer::transform()` returns exactly: `type`, `label`, `game_map`, `location`, `rules`, `sources` (each `ResolvedAreaGemSource::toArray()` plus a `rolled_gem` key holding the exact `RolledGemTransformer` output for that source's `rolledGemId()`, loaded with `Gem::findOrFail()`), `character_power_reduction`, `monster_effects` (`ResolvedAreaGemMonsterEffects::toArray()`, unchanged), `reward_effects` (`ResolvedAreaGemRewardEffects::toArray()`, unchanged), `crafting_skill_bonuses` (positive-only, resolved via `GameSkill::whereIn(...)->orderBy('name')`, returned as `{id, name, bonus}`), `rarity_effects` (`ResolvedAreaGemRarityEffects::toArray()`, unchanged).

`rules` are built by four small private methods (`mapRules`, `locationRules`, `mapGemWorldRules`, `locationGemWorldRules`), matched on `$effects->contextType()`, each deriving its sentences strictly from which `ResolvedAreaGemSource` types are actually present in `$effects->sources()` and from those sources' own `monsterMultiplier()`/`rewardMultiplier()`/`reductionMultiplier()` values — no multiplier is recalculated, and a source that does not exist is never mentioned (e.g. a `location_gem_world` context resolved with no rolled Location Gem yet only states the parent Map Monster-population fact and, when applicable, the parent Map Gem's reduction rule; it never fabricates Location contributions).

## Exact contextual Map-vs-Location eligibility behavior

`GemWorldService::resolveEntry()` is the single source of truth used by both `context()`'s `entry` field and `enter()`'s destination resolution:

- Not inside a generated world, standing exactly on a Location (same `x`/`y`/`game_map_id` convention as `AreaGemEffectService`/`TraverseService`) → only a Location Gem entry is ever considered; there is no fallback to the Map Gem under any circumstance, even when the Location has no eligible Location Gem World.
- Not standing on a Location → only a Map Gem entry is considered.
- Already inside a generated Gem World → `entry` is always `null`.

Eligibility for each is the exact five-part check from the task (profile exists, `rolled_gem_id` non-null, `rolledGem` exists, `generatedMap` exists with the matching `generated_map_type`/`game_*_gem_paramter_id`/`generated_parent_game_map_id`).

## Explicit confirmation that no chooser exists

`entry` is a single nullable object, never an array/list of candidate destinations. The frontend Map card never renders a Map-vs-Location choice; it renders exactly one button using the backend's own `entry.label` (`Enter Map Gem` or `Enter Location Gem`).

## Entry behavior

`GemWorldService::enter()`: automation-restriction guard → reject if already inside a generated world (`You are already inside a Gem World.`) → resolve the single valid entry via `resolveEntry()` → reject when none (`There is no Gem World available from your current Map or Location.`) → `TraverseService::travel($entry['generated_game_map']['id'], $character)` → `successResult(['message' => 'You entered the Gem World.'])`. The client never supplies a Map id; `GemWorldController::enter()` takes no request body beyond the route's `{character}`.

## Exit behavior

`GemWorldService::exit()`: automation-restriction guard → reject when not inside a generated world (`You are not inside a Gem World.`) → reject when `generatedParentMap` is missing (`This Gem World does not have a valid parent Map.`) → `TraverseService::travel($parentMap->id, $character)` → `successResult(['message' => 'You exited the Gem World.'])`. No return-location field, coordinates, or session table was added anywhere; the generated Map's existing `generated_parent_game_map_id` (via `generatedParentMap()`) remains the sole authority.

## Ordinary Traverse generated-map hardening

`TraverseService::canTravel()` rejects any destination Game Map that `isGeneratedGemMap()` or has `can_traverse = false`, before `mapType()` is ever consulted. Verified by two new `MapControllerTest` cases (a forged `/api/map/traverse/{character}` POST directly at a generated Map Gem World, and a POST at an ordinary Map with `can_traverse = false`); both assert 422 and that the Character's Map is unchanged.

**Existing-test consequence discovered and fixed**: `game_maps.can_traverse` defaults to `0` at the database level (`database/schema/mysql-schema.sql:868`), and no migration changes that default. The pre-existing `test_traverse_moves_character_to_new_plane_when_required_item_is_owned` created its Hell Map without setting `can_traverse`, which the *old* `canTravel()` never checked for named-plane Maps. Adding the required `can_traverse` guard therefore made that legitimate test fail (a real Hell Map would always have `can_traverse = true` in production). Fixed by adding `'can_traverse' => true` to that one existing fixture; its assertions and behavior were not weakened or otherwise changed. This is the only pre-existing test whose fixture needed correction.

## Parent-plane message handling

`TraverseService::travel()`'s narrative/global-message block for Shadow Plane, Hell, Purgatory, The Ice Plane, Twisted Memories, and Delusional Memories is now inside the extracted `sendPlaneNarrativeMessages()` and is only invoked when the destination is not a generated Gem Map. The generic `You have traveled to: {name}` message, `ServerMessageHandler::handleMessage()` call, and `UpdateCharacterStatus` event fire unconditionally exactly as before. Normal (nongenerated) plane traversal narrative behavior is unchanged — proven by the untouched `test_traverse_moves_character_to_new_plane_when_required_item_is_owned`, which still travels to a real (nongenerated) Hell Map and passes.

## Frontend files created

Shared Gem factual contracts/components:
- `resources/js/game/reusable-components/gems/api/definitions/area-gem-source-definition.ts`
- `resources/js/game/reusable-components/gems/api/definitions/gem-world-source-definition.ts` (extends the above with `rolled_gem: RolledGemDefinition`)
- `resources/js/game/reusable-components/gems/api/definitions/area-gem-context-definition.ts` (`AreaGemContextDefinition` plus the `AreaGemMonsterEffectsDefinition`/`AreaGemRewardEffectsDefinition`/`AreaGemCraftingSkillBonusDefinition`/`AreaGemRarityEffectsDefinition` field-exact sub-shapes)
- `resources/js/game/reusable-components/gems/components/area-gem-context.tsx` — permission-neutral renderer of only positive/non-zero effects, grouped exactly as specified (Character, Currency and Drops, Crafting, Monster Combat including atonement, Monster Rewards); uses `formatPercent` only, no multiplier math.
- `resources/js/game/reusable-components/gems/components/area-gem-source-card.tsx` — one whole native `<button>` per source with an accessible name `"{Map Gem|Location Gem}: {profile_name}"`.
- `resources/js/game/reusable-components/gems/components/rolled-gem-source-detail.tsx` — factual source identity/multipliers plus the existing `RolledGemStats`.
- `resources/js/game/reusable-components/gems/definitions/player-rolled-gem-display-groups.ts` — `MAP_GEM_PLAYER_DISPLAY_GROUPS` (includes `character_power_reduction`) and `LOCATION_GEM_PLAYER_DISPLAY_GROUPS` (does not), mirroring the existing Admin Map/Location Gem field groupings.
- Plus local `types/*-props.ts` files for the three new components above (required by the component-creation/type-safety skills; not separately enumerated in the task's file list but directly necessary).

Map API contracts/hooks:
- `resources/js/game/components/map-section/api/enums/gem-world-api-urls.ts`
- `resources/js/game/components/map-section/api/definitions/gem-world-status-definition.ts`, `gem-world-entry-definition.ts`, `gem-world-exit-definition.ts`
- `resources/js/game/components/map-section/api/hooks/definitions/use-gem-world-context-definition.ts`, `use-gem-world-context-params.ts`, `use-gem-world-mutation-definition.ts`
- `resources/js/game/components/map-section/api/hooks/use-gem-world-context.ts` — GET hook with `AbortController`/request-generation stale-response safety (mirroring `use-craft-set-recommendation.ts`); re-fetches only when `game_map_id`/`x`/`y` change; skips the request entirely (no wasted call) when `character_id <= 0`.
- `resources/js/game/components/map-section/api/hooks/use-enter-gem-world.ts`, `use-exit-gem-world.ts` — POST hooks with no request body, `AbortController` cleanup on unmount, and a submitting-ref guard against duplicate submissions; neither hook emits the Map refresh or closes the SidePeek itself — that is the caller's responsibility per the task.

SidePeek:
- `resources/js/game/components/side-peeks/map-actions/gem-world/gem-world.tsx`
- `resources/js/game/components/side-peeks/map-actions/gem-world/types/gem-world-props.ts`

Map-opening adapter:
- `resources/js/game/components/map-section/hooks/use-open-gem-world-side-peek.ts`
- `resources/js/game/components/map-section/hooks/types/use-open-gem-world-side-peek-definition.ts`

## Frontend files modified

- `resources/js/game/components/actions/partials/floating-cards/map-section/map-card.tsx` — added the Gem action section only (existing movement buttons, Map rendering, Teleport/Set Sail/Traverse/Conjure/My Kingdoms/View Location behavior untouched). Calls `useGemWorldContext` (keyed on `game_map_id`/`x`/`y`), `useExitGemWorld`, `useOpenGemWorldSidePeek`, `useEmitMapRefresh`. Renders, in order: an inline `ApiErrorAlert` when the context GET fails (other Map controls remain usable), an accessible `role="status"` loading line only while loading and no data yet, then exactly one of: inside-Gem-World section (`Gem World: {label}`, `View Gem Effects`, and an `Exit Gem World` `LoadingButton` disabled when `!canMove`, calling the exit endpoint immediately with no confirmation dialog), eligible-entry section (compact `Map Gem: {name}`/`Location Gem: {name}` text derived from `entry.context.sources` matching `entry.type`, plus one full-width button using the backend's exact `entry.label`), current-effects-only section (`View Gem Effects` only), or nothing when no Gem context exists at all. The Map floating card is never replaced/unmounted for any of this.
- `resources/js/game/components/side-peeks/base/component-registration/side-peek-component-registration-enum.ts` — added `MAP_ACTIONS_GEM_WORLD`.
- `resources/js/game/components/side-peeks/base/component-registration/side-peek-component-props-map.ts` — added the `MAP_ACTIONS_GEM_WORLD: GemWorldProps` entry.
- `resources/js/game/components/side-peeks/base/component-registration/side-peek-component-registry.ts` — registered `GemWorld` with `content_scroll_mode: SidePeekContentScrollMode.COMPONENT`.
- `resources/js/game/reusable-components/monster/api/definitions/monster-detail-definition.ts` — the local `MonsterGemEffectSourceDefinition` interface (an exact field-for-field duplicate of the new shared source shape) was removed and `sources: AreaGemSourceDefinition[]` now imports the shared definition. No other Monster presentation behavior/field was changed; `monster-gem-effect-context-card.tsx` needed no change since it consumes `(typeof context.sources)[number]` structurally.

## Gem SidePeek information hierarchy

`gem-world.tsx` renders, in this exact order, inside one `overflow-y-auto` component-owned scroll region: an inline enter-error `Alert` when present, `Rules Applied` (semantic `<ul>` of the backend `rules` strings, omitted entirely when empty), the shared `AreaGemContext` effects renderer, then `Gem Sources` (one `AreaGemSourceCard` button per resolved source, `gap-2` between cards). It owns only local `selectedSource` state, the enter mutation, and the footer registration; it performs no Gem math.

## Nested source drill-down behavior

Clicking a source card opens a local `StackedCard` with `content_mode={StackedCardContentMode.FULL_BLEED}` and ARIA label `"{Map Gem|Location Gem}: {profile_name}"`, rendering `RolledGemSourceDetail` (source identity/multipliers + `RolledGemStats` with the Map/Location player display groups selected by `source.type`). This is ordinary local StackedCard composition inside the existing SidePeek/StackedCard portal system; no ScreenManager and no second SidePeek host were created.

## Exact existing Gem rules displayed

Rule text is generated entirely server-side by `GemWorldContextTransformer`'s four context-specific builders (see "Gem World context response contract" above); the frontend never constructs, infers, or recalculates a rule sentence — `AreaGemContext.tsx` and `gem-world.tsx` only render the backend `rules`/`monster_effects`/`reward_effects`/`crafting_skill_bonuses`/`rarity_effects` arrays/values as given.

## Websocket/live-state behavior preserved

Entry/exit rely entirely on the existing `TraverseService::travel()` cascade (`UpdateMap`, `UpdateBaseCharacterInformation`, `UpdateMonsterList`, `MoveTimeOutEvent`, `UpdateCharacterStatus`) already consumed by `game-data-provider.tsx`/`character-updates-wire.tsx`/`monster-updates-wire.tsx`, none of which were modified. `useEnterGemWorld`/`useExitGemWorld` perform no Character or Monster GET; their callers (`gem-world.tsx` for enter, `map-card.tsx` for exit) only call `emitShouldRefreshMap(true)` (the existing `Traverse.REFRESH_MAP` event emitter shared with Teleport/Set Sail/Traverse) and, for enter, close the SidePeek via the existing `useCloseSidePeekEmitter`.

## Monster list integration proof

`MonsterListService`/`MonsterStatsService`/`MonsterTransformer`/`MonsterListService::resolveMonsterDataSetForCharacter()` were not modified. Both new integration tests in `GemWorldControllerTest` hit the real `GET /api/monster-list/{character}` and `GET /api/monster-stat/{monster}/{character}` endpoints after a real Gem World entry and assert against the actual cached rows built by `BuildMonsterCacheService::buildAll()` (called directly in the test, as the existing `MonstersApiControllerTest`/`MapGemsApiControllerTest` tests already do via `resolve(BuildMonsterCacheService::class)`), never a manually reconstructed expectation.

## Map Gem World Monster-stat integration proof

`test_entering_a_map_gem_world_returns_the_cached_gem_affected_monster_and_effective_stats`: creates a parent Map, a regular Monster on it, a rolled Map Gem (`enemy_strength_increase = 0.5`) with its generated Map Gem World, builds the real Monster cache, enters through `POST /api/map/gem-world/enter/{character}`, then asserts `GET /api/monster-stat/{monster}/{character}` returns `gem_effect_context_preview.type === 'map_gem_world'` and a `changed_values` row for `field === 'str'` whose `effective_value` equals the exact cached `str` value read from `Cache::get(MonsterCacheKey::MONSTERS->value)[$generatedMap->name]['data']` — the same cache row the production code itself serves, never a manually recomputed number.

## Location Gem World Monster-stat integration proof

`test_entering_a_location_gem_world_returns_the_cached_gem_affected_monster_and_effective_stats`: identical structure for a rolled Location Gem (`enemy_strength_increase = 0.6`) and its generated Location Gem World; asserts entry lands on the Location generated Map (not the Map Gem World, proving Location-over-Map precedence at the actual persisted-state level, not just the read model) and that the Monster stat endpoint's `gem_effect_context_preview.type === 'location_gem_world'` with the same cached-value comparison.

## Exact tests created/updated

Created:
- `tests/Unit/Game/Gems/Transformers/RolledGemTransformerTest.php` — 3 tests: every factual field is returned exactly; crafting Skill ids become sorted `{id, name}` identities; `is_active` is exactly the supplied boolean. Real `Gem`/`GameSkill` models via `CreateGem`/`CreateGameSkill`, no mocks.
- `tests/Feature/Game/Maps/Controllers/Api/GemWorldControllerTest.php` — 15 tests: context Map Gem entry (with effective-value assertion against a real `AreaGemEffectService` computation); context Location Gem wins over an eligible Map Gem; context standing on a Location with no eligible Location Gem blocks the Map fallback (and still exposes factual `current_context`); context with no Gems at all (`entry`/`current_context`/`exit` all null, `inside_gem_world` false); context inside a generated Map Gem World (no entry, exact exit); context inside a generated Location Gem World (no entry, exact exit); entry travels to the generated Map Gem World; entry travels to the generated Location Gem World and not the Map Gem World; entry rejected when standing on a Location without an eligible Location Gem (exact message, Character map unchanged); entry rejected when already inside a generated world (exact message); exit travels to the exact generated parent Map; exit rejected when not inside a Gem World (exact message); Character-ownership security test (`getJson` with another Character's id → 422, `"You don't have permission to do that."`, following the existing `IsCharacterWhoTheySayTheyAreMiddlewareTest` style); the two Monster-integration acceptance tests described above.

Updated:
- `tests/Feature/Game/Maps/Controllers/Api/MapControllerTest.php` — added `test_ordinary_traverse_rejects_a_forged_request_directly_to_a_generated_gem_world` and `test_ordinary_traverse_rejects_a_map_with_can_traverse_disabled`; fixed the pre-existing Hell-Map fixture in `test_traverse_moves_character_to_new_plane_when_required_item_is_owned` to set `can_traverse => true` (see "Ordinary Traverse generated-map hardening" above for why this was required and why it does not weaken that test). No other existing test in this file was changed.

All fixtures use only existing `Tests\Traits\Create*` traits (`CreateGameMap`, `CreateLocation`, `CreateGameMapGemParamter`, `CreateGameLocationGemParamter`, `CreateGem`, `CreateMonster`) and the existing `CharacterFactory` — no direct model factories, no test helper methods beyond the existing `tearDown()` lifecycle pattern already used by `MapControllerTest`, no reflection.

## Exact commands run and factual results

- `php -d memory_limit=-1 -d max_execution_time=0 vendor/bin/pest --filter='GemWorldControllerTest|RolledGemTransformerTest|MapControllerTest' tests/Feature/Game/Maps/Controllers/Api/GemWorldControllerTest.php tests/Unit/Game/Gems/Transformers/RolledGemTransformerTest.php tests/Feature/Game/Maps/Controllers/Api/MapControllerTest.php` (the bare `--filter` without explicit file paths cannot boot in this repository through plain `phpunit`/without file targeting for the same pre-existing Pest-suite-loading reason recorded in the Phase 2 proof; pointing directly at the three target files reaches exactly the required classes) — **41 passed (144 assertions)**, 0 failures, run twice (once before, once again after Pint's formatting pass) with identical results.
- `./vendor/bin/pint` (targeted at the touched backend files, then `./vendor/bin/pint --test` repository-wide) — targeted run: `fixed` on the 6 new/changed backend classes (Pint stripped the `@param`/`@return` tags this repository's actual configuration removes from simple typed signatures, consistent with the Phase 2 precedent); repository-wide `--test` afterward: **passed**.
- `yarn type-check` — **passed**, 0 errors (run twice: once before `eslint --fix`, once after).
- `yarn lint` — initial run surfaced import-order and Prettier-formatting issues only in files created/edited this phase; fixed via `eslint --fix` scoped to exactly those files; final `yarn lint` — **0 errors, 7 warnings**, all 7 in files this phase never touched (`use-own-game-map-move.ts`, `use-get-set-equippability-details.ts`, `use-move-item-to-set.ts`, `ui/alerts/alert.tsx`).
- `yarn cleanup` — ran Prettier across the repository; every file touched/created this phase reported `unchanged`; no unrelated file was modified.
- `yarn unused-files-check` — **passed**: "There don't seem to be any unimported files."
- `yarn build:dev` — **passed**, Vite build completed successfully.
- Mandatory prohibited-pattern audit over every changed/created PHP and TypeScript file this phase: no `final class`, no `declare(strict_types=1)`, no manual scalar casts, no debug output, no `resolve()`/`app()` in application code, no reflection/non-lifecycle test helpers/direct model factories/manual job `handle()` calls, no `console.*`, no `eslint-disable`/`@ts-ignore`/`@ts-expect-error`, no `any` — zero matches in this phase's own files (the `eslint-disable` matches found by the audit grep all belong to pre-existing files this phase never opened for editing).

## Excluded areas not touched

`AreaGemEffectService` Gem math, `BuildMonsterCacheService` cache-building formulas, Monster source-map selection, `MonsterTransformer`, `MonsterListService`, `MonsterStatsService` (only read, never modified — no Phase 1 import/type change was required here), Character stat/reward/drop formulas, Raid/Weekly Fight/Celestial/Cave of Memories exclusions, `create:gem-worlds`/Gem roll generation/Gem World generation/image generation/tile generation/mass import orchestration, `MapName`/`mapType()`, Teleport/Set Sail/walking/Conjure/normal Traverse dropdown behavior, Admin Gem mutation/activation/history endpoints (contract byte-identical), Information routes, the existing SidePeek-local factual stacking architecture.

## Confirmation

No migration was created or modified. No operational Artisan command (`create:gem-worlds`, `mass:import-game-data`, `create:character-attack-data`, `generate:monster-cache`, `create:quest-cache`, `import:game-data`) was executed. No Gem World was generated by running the Phase 2 command; every generated `GameMap` used by the new tests was constructed directly via `createGameMap([...])` with explicit `generated_map_type`/`generated_parent_game_map_id`/`game_map_gem_paramter_id`/`game_location_gem_paramter_id` fields, following the exact same pattern already established by `GemWorldGenerationServiceTest`. No browser testing was performed and none is claimed.

## Final status

I am done
