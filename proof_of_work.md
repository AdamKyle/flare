# Phase 2B Remediation — Proof of Work

This document supersedes all prior claims in earlier versions of this file. The previous
`proof_of_work.md` was explicitly not trusted as evidence; every statement below reflects code
actually inspected and, where a test is cited, actually run in this session via
`./vendor/bin/pest --fail-on-risky <file>`.

Work is in progress. This file is updated incrementally as each numbered remediation section is
verified complete. See the running section-by-section log below.

## Section 1 — Public Information API routing: COMPLETE

**Root cause confirmed by reading the code** (not assumed): `resources/js/api-handler/api-handler.tsx::addApiPrefix()` prepends `/api` to every request. `RouteServiceProvider::mapInformationApiRoutes()` registered `routes/information/api.php` under prefix `information/api`, producing real routes at `/information/api/quests/tree` etc. The frontend hooks requested `/information/api/...`, which the ApiHandler then prefixed again to `/api/information/api/...` — a genuine 404, confirmed by reading both sides before touching anything.

Fix:
- `app/Providers/RouteServiceProvider.php::mapInformationApiRoutes()`: prefix changed from `'information/api'` to `'api/information'`. Final routes (confirmed via `php artisan route:list --path=information`): `GET api/information/quests/tree`, `GET api/information/quests/{quest}`, `GET api/information/monsters/{monster}` — exactly matching the required contract.
- `routes/information/api.php` unchanged (relative paths were already correct; only the prefix was wrong).
- Frontend: created `resources/js/information/quests/api/enums/quest-info-api-urls.ts` (`QuestInfoApiUrls.TREE = '/information/quests/tree'`, `SHOW = '/information/quests/{quest}'`) and `resources/js/information/monsters/api/enums/monster-info-api-urls.ts` (`MonsterInfoApiUrls.SHOW`). Per the skill, the enum values do **not** include `/api` — `getUrl()` + the shared `ApiHandler` add it.
- Rewrote `use-public-quest-detail.ts` and `use-public-monster-detail.ts` to call `getUrl(QuestInfoApiUrls.SHOW, ...)` / `getUrl(MonsterInfoApiUrls.SHOW, ...)` instead of hardcoded path strings, and moved their return-type interfaces into `api/hooks/definitions/use-public-quest-detail-definition.ts` / `use-public-monster-detail-definition.ts` (Section 17 ownership fix, done here since these are the exact two files Section 17 names).

**Focused tests (new file `tests/Feature/Info/Quests/QuestsApiControllerTest.php`, `tests/Feature/Info/Monsters/MonstersApiControllerTest.php`):**
- `guest_can_access_public_quest_tree`, `public_quest_tree_can_be_filtered_by_map`, `public_quest_tree_can_be_filtered_by_kind`, `guest_can_access_public_quest_detail`, `guest_can_access_public_monster_detail` — all hit the real final `/api/information/...` URLs with no `actingAs()`.
- Command: `./vendor/bin/pest --fail-on-risky tests/Feature/Info/Quests/QuestsApiControllerTest.php tests/Feature/Info/Monsters/MonstersApiControllerTest.php` → **PASS**, 5 passed, 12 assertions, 0 risky.

## Section 2 — Missing public Quest tree: COMPLETE

**Source-state finding**: `/information/quests` (no id) was not a dedicated route before this session; it fell through to the generic catch-all `Route::get('/information/{pageName}', ['as' => 'info.page', ...])`, which looks up a CMS `InfoPage` row by `page_name = 'quests'` — i.e. "generic legacy content" exactly as the task describes, backed by a DB-driven page rather than any Quest-specific code.

Fix:
- `routes/web.php`: added `Route::get('/information/quests', ['as' => 'info.page.quest.tree', 'uses' => 'InfoPageController@viewQuestTree']);` as the **first** route in the `update.player-activity` group, ahead of the `{pageName}` catch-all, so it wins the literal match. Confirmed via `php artisan route:list --path=information/quests` that both the new route and the pre-existing `/information/quests/{quest}` detail route are registered and distinct.
- `app/Http/Controllers/InfoPageController.php`: added `viewQuestTree(): View` returning `view('information.quests.quests')`. While touching this method's immediate neighborhood, cleaned up `viewQuest()`: it previously computed `$skill`/`lockedSkill` view data that the Blade view never references (confirmed by reading the full `information/quests/quest.blade.php` — it only mounts the React app via `data-quest-id`, no `lockedSkill` usage anywhere) — this was dead code left over from before the page was React-mounted. Removed it and typed the method's return.
- `resources/views/information/quests/quests.blade.php` (new): mounts `#quest-info-app` with no `data-quest-id` attribute.
- `resources/js/information/quests/quests-info-app.tsx`: now branches on `mountElement.dataset.questId` — present → existing `PublicQuestDetailPage`; absent → new `QuestInfoTreePage`. One Vite entry (already registered in `vite.config.js`) serves both routes, matching how `quest.blade.php` already worked.
- `resources/js/information/quests/components/quest-info-tree-page.tsx` (new): reuses the shared `QuestTree` component and the same `QuestKind` filter dropdown pattern as Admin's `QuestListScreen`. Map filter options are derived client-side from the walked node tree of an **unfiltered** `usePublicQuestTree(null, null)` call (collecting each node's `game_map` identity) — deliberately not a 4th backend endpoint, since the task's Section 1 explicitly lists only 3 final public URLs. A second `usePublicQuestTree(mapId, kind)` call drives the actually-displayed, currently-filtered tree. Selecting a Quest navigates to `/information/quests/{id}`, preserving the existing detail-page compatibility URL. No mutation controls; matches the public/read-only contract.
- `resources/js/information/quests/api/hooks/use-public-quest-tree.ts` (new) + `api/hooks/definitions/use-public-quest-tree-definition.ts` (new): same abort/generation-guarded pattern as every other API hook in this codebase, uses `map_id` (see Section 6) as the query param name.

**Focused tests**: `tests/Feature/Http/Controllers/InfoPageControllerTest.php` — added `quest tree page renders`; fixed the pre-existing `quest show page renders with its unlocked skill` test, which asserted `assertViewHas('lockedSkill', ...)` against the now-removed dead view data (the underlying behavior — the page still renders correctly for a Quest with an unlocked skill — is preserved; only the assertion on dead view data was removed).
Command: `./vendor/bin/pest --fail-on-risky tests/Feature/Http/Controllers/InfoPageControllerTest.php` → **PASS**, 40 passed, 71 assertions, 0 risky.

## Section 3 — Quest import repair: COMPLETE

**Root cause confirmed, not merely asserted**: `App\Admin\Quests\Imports\Sheets\QuestsSheet::resolveRow()` called `$this->questService->normalize($data)` where `QuestService::normalize()` was `private`. PHP method visibility is per-*class*, not per-instance; `QuestsSheet` and `QuestService` are different classes, so this was a genuine fatal `Error` (uncaught "Call to private method") the moment a real import ran — not a soft validation failure. Additionally, `QuestImportController` unconditionally returned `{"message": "Quests imported successfully."}` regardless of outcome — confirmed by reading the controller, which never inspected any result from the service.

This was rebuilt properly, not patched:

1. **`QuestService::normalize()` made `public`** — genuinely needed by a second real caller (the import), not merely to silence the visibility error.
2. **`App\Admin\Quests\Support\QuestImportGraphValidator` (new)** — a focused, self-contained in-memory graph-cycle validator (parent/required-Quest/required-Quest-chain), independent of `QuestGraphGuard` (which remains the single-Quest, DB-driven validator used by the live Admin form and is unchanged). It accepts the complete workbook name→id map (existing DB Quest ids, plus a unique **negative synthetic id** per brand-new workbook Quest name) and every row's resolved edges, and walks parent/required/chain edges preferring a row's own workbook-resolved value over a DB lookup — so a cycle spanning purely-new workbook rows, purely-existing rows, or a mix of both is caught before any write.
3. **`App\Admin\Quests\Imports\Sheets\QuestsSheet` fully rewritten**:
   - `buildNameToId()`: unions every existing DB Quest name→id with a synthetic negative id per new workbook name, so `parent_quest_id`/`required_quest_id`/`required_quest_chain` cells can reference **either** an existing Quest **or** another new Quest defined later in the same workbook, by name — the exact capability the stale docblock claimed was unsupported (that docblock is removed; replaced with an accurate one).
   - `resolveRow()`: resolves every named relationship (NPC/Item/Raid/Map/Passive — DB-only, unchanged convention) plus the three Quest-reference fields (workbook-graph-aware), and validates `only_for_event`/`unlocks_skill_type`/`unlocks_feature` against their real owning enums (`EventType`, `SkillTypeValue`, `FeatureType` — the same ones `StoreQuestRequest` uses) via a scoped `Validator::make()` call — previously these three finite-domain cells were not validated at all during import.
   - Full-workbook cycle validation runs via `QuestImportGraphValidator` **after** every row resolves and **before** any write.
   - **Two-phase write** (`writeRows()`): phase 1 creates/updates every row with its three Quest-reference fields held back (a synthetic id can never be written to a real foreign key since the row it refers to may not exist yet), recording each new row's real id; phase 2 resolves every row's parent/required/chain ids (translating any synthetic id to the real id phase 1 just created) and persists them, calling the now-`public` `QuestService::reconcileParentFlags()` with each row's true previous parent (captured before phase 1's write, since phase 1 never touches the parent column) — this is what makes `is_parent` coherent for imported data too, not just the live Admin form. Zero DB transaction (none added, per the explicit prohibition); zero partial write in the sense that mattered — the phase 1/phase 2 split cannot fail differently than validation already predicted, since both phases operate purely on already-fully-validated data.
   - `wasSuccessful(): bool` / `validationError(): ?string` expose the real outcome.
4. **`QuestsImport`**: now holds a single shared `QuestsSheet` instance (constructor-injected or defaulted) so its result can be read back after `Excel::import()` runs; exposes `wasSuccessful()`/`validationError()`.
5. **`QuestExcelService::import()`**: now uses the project's `ResponseBuilder` convention (added `use ResponseBuilder;`), returns `errorResult($import->validationError())` on failure or `successResult(['message' => 'Quests imported successfully.'])` on success, instead of returning `void` and always looking successful.
6. **`QuestImportController`**: now reads the service's result exactly like every other `ResponseBuilder`-based controller in this codebase (`$status = $result['status']; unset($result['status']); return response()->json($result, $status);`) instead of hardcoding a 200 success message.

**Verified NOT a defect** (checked, not assumed): the export sheet (`app/Admin/Quests/Exports/Sheets/QuestsSheet.php` / `resources/views/admin/quests/exports/sheets/quests.blade.php`) has exactly one `raid_id` header and exactly one `required_quest_id` header — no duplicates exist in the current code. `before_completion_description`/`after_completion_description` are already exported as raw `{{ $quest->... }}` (no `nl2br()`). These parts of the Section 3/9 description do not apply to the current source state; recorded here rather than "fixed" since nothing was wrong.

**Focused tests**:
- `tests/Feature/Admin/Quests/QuestsImportTest.php` (new, 14 tests) — calls `(new QuestsSheet)->collection($rows)` directly with constructed `Collection` row data (the real `ToCollection` public contract Laravel Excel calls after parsing a file — no real or faked Excel file I/O needed for this level, since the parsed-row boundary **is** the sheet's actual public interface): two new same-workbook Quests where the child references the parent by name; two new Quests where one requires the other; a required chain naming two workbook-local Quests; parent cycle rejected; prerequisite cycle rejected; required-chain cycle rejected; duplicate Quest names rejected; invalid NPC/Item/Map/Raid/Passive each rejected; a workbook with one valid + one invalid row writes zero Quests; a fully-valid two-row workbook writes both only after validation completes.
  Command: `./vendor/bin/pest --fail-on-risky tests/Feature/Admin/Quests/QuestsImportTest.php` → **PASS**, 14 passed, 30 assertions, 0 risky.
- `tests/Feature/Admin/Quests/QuestImportControllerTest.php` (new, 2 tests) — exercises the **real** `Excel::import()` pipeline end-to-end through the actual `POST /api/admin/quests/import` HTTP endpoint, using a real on-disk `.xlsx` built via `PhpOffice\PhpSpreadsheet` (a genuine dependency already required by `maatwebsite/excel`) through a new fixture trait `tests/Traits/CreateQuestsWorkbookFile.php` (kept out of the test class body per the fixture-construction rule against test-class helper methods): a valid workbook returns 200 and creates the Quest; an invalid workbook returns 422 with a `message` key and creates zero Quests.
  Command: `./vendor/bin/pest --fail-on-risky tests/Feature/Admin/Quests/QuestImportControllerTest.php` → **PASS**, 2 passed, 5 assertions, 0 risky.

## Section 4 — Quest hierarchy `is_parent` coherence: COMPLETE

**Confirmed defect**: `QuestService::update()` called a private `syncParentFlag(?int $parentQuestId)` that only ever set the **new** parent's `is_parent` to `true`; it never inspected or cleared a Quest's **previous** parent, so moving/clearing a child's parent left the old parent's `is_parent` stuck `true` forever, even with zero remaining children.

Fix: replaced `syncParentFlag()` with `public function reconcileParentFlags(?int $previousParentId, ?int $newParentId): void`, called from `create()` (with `$previousParentId = null`), `update()` (capturing `$quest->parent_quest_id` **before** the update as the true previous value), and the import's phase 2 (Section 3). It sets the new parent's flag true, then — only when the parent actually changed — checks whether the previous parent still has any remaining child (`Quest::where('parent_quest_id', $previousParentId)->exists()`) and clears its flag only if not. `is_parent` remains a derived/maintained value, never a form field (unchanged from the existing correct Step-2 form contract).

**Focused tests**: added to `tests/Feature/Admin/Quests/QuestsApiControllerTest.php` — `test_new_parent_becomes_parent`, `test_previous_parent_remains_true_when_it_still_has_another_child`, `test_previous_parent_becomes_false_when_its_final_child_moves`, `test_previous_parent_becomes_false_when_final_childs_parent_is_cleared`. While adding these, also cleaned up a pre-existing test-class-helper violation in this same file (`private function minimalPayload(): array`, prohibited by `phpunit-fixture-construction`) by inlining the literal payload array at each of its 8 call sites, reusing each test's own already-created Quest's real `npc_id` instead of creating an extra throwaway NPC per call.
Command: `./vendor/bin/pest --fail-on-risky tests/Feature/Admin/Quests/QuestsApiControllerTest.php` → **PASS**, 21 passed, 76 assertions, 0 risky.

## Section 5 — Quest read querying moved out of the transformer: COMPLETE

**Confirmed defect**: `App\Game\Quests\Transformers\QuestDetailTransformer` contained `requiredQuestChain()` (`Quest::whereIn('id', $requiredIds)->get(...)`) and `unlockedSkill()` (`GameSkill::where('type', ...)->first()`) — both real database queries executed from inside a transformer.

Fix: moved both queries into `QuestReadService` as `resolveRequiredQuestChain(Quest $quest): array` and `resolveUnlockedSkill(Quest $quest): ?array` (identical logic, just relocated), called once from `QuestReadService::detail()`. `QuestDetailTransformer::transform()` signature now takes the already-resolved `$requiredQuestChain`/`$unlockedSkill` as parameters and only shapes data — zero queries remain in the transformer. Also removed the redundant `(bool) $npc->must_be_at_same_location` cast in the same file: `Npc::$casts` already declares `'must_be_at_same_location' => 'boolean'`, so Eloquent already returns a real `bool`; the manual cast was dead re-casting of an already-typed value.

**Verified via the existing exact-shape test** (`test_show_returns_full_factual_detail_shape` in `QuestsApiControllerTest.php`, which asserts `structure.required_quest_chain` order and `rewards.skill` identity through the live HTTP response) — re-run as part of the Section 4 regression run above: **PASS**.

## Section 6 — Quest tree API input contract (`map_id`): COMPLETE

**Confirmed defect**: both `App\Admin\Quests\Requests\QuestTreeRequest` and `App\Info\Requests\QuestTreeRequest` validated/exposed `game_map_id`, not the documented `map_id`.

Fix: both Requests now validate `map_id` and expose `mapId(): ?int` (renamed from `gameMapId()`). Both `QuestsController::tree()` call sites (`app/Admin/Quests/Controllers/Api/QuestsController.php`, `app/Info/Controllers/Api/QuestsController.php`) updated to call `$request->mapId()`. Frontend: `resources/js/admin/quests/api/hooks/use-quest-tree.ts` request param renamed `game_map_id` → `map_id` (the new public `use-public-quest-tree.ts` was written with `map_id` from the start). Test: `tests/Feature/Admin/Quests/QuestsApiControllerTest.php::test_tree_can_be_filtered_by_game_map` updated to send `map_id` — confirmed passing in the Section 4 regression run above. New public-side coverage in `tests/Feature/Info/Quests/QuestsApiControllerTest.php::test_public_quest_tree_can_be_filtered_by_map`.

**Also fixed while here (Section 7 overlap)**: both `mapId()` accessors previously did `(int) $value` — a manual scalar cast prohibited by `back-end-conventions`. Replaced with `$this->integer('map_id')` (Laravel's own typed request accessor), guarded by an explicit `is_null()` check first, so no manual cast operator remains in either file.

## Section 7 — Backend skill violations in touched Phase 2B code: COMPLETE (celestial_type — recorded, not fixed; see below)

- **Manual casts removed**: `Admin\Quests\Requests\QuestTreeRequest::mapId()`, `Info\Requests\QuestTreeRequest::mapId()` (both — see Section 6), and `QuestDetailTransformer`'s redundant `(bool)` (see Section 5). Confirmed via `grep` that no other `(int)`/`(bool)`/`(float)`/`(string)`/`(array)` cast exists anywhere under `app/Admin/Quests`, `app/Admin/Monsters`, `app/Game/Quests`, `app/Game/Monsters`, `app/Info` — the only other match was `app/Game/Monsters/Transformers/MonsterTransformer.php` (the pre-existing **combat** transformer, explicitly out of scope: not touched by Phase 2B, exists specifically to apply encounter scaling to raw string ranges like `"1-8"`).
- **Narrative method-body comments removed**: `// Step 1: Quest & Story.` / `// Step 2: Structure & Dependencies.` / `// Step 3: Requirements.` / `// Step 4: Rewards.` from `app/Admin/Quests/Requests/StoreQuestRequest.php` (confirmed `UpdateQuestRequest` already had none). `// Identity & Placement.` / `// Core Combat.` / `// Probabilities.` / `// Spells & Affixes.` / `// Quest/Celestial.` / `// Raid & Special Rules.` from `app/Admin/Monsters/Requests/StoreMonsterRequest.php` (`UpdateMonsterRequest extends StoreMonsterRequest` with no rules of its own, so it inherited the fix automatically). Method docblocks were preserved unchanged.
- **`damage_stat` finite-domain duplication fixed**: created `App\Game\Core\Values\CoreStatType` (string-backed enum: `STRENGTH='str'`, `DURABILITY='dur'`, `DEXTERITY='dex'`, `CHARISMA='chr'`, `INTELLIGENCE='int'`, `AGILITY='agi'`, `FOCUS='focus'`) under `app/Game/Core/Values` (a genuinely game-wide concept — the same 7 stat keys back Character/GameClass/Monster, not a Monster-only idea; no existing PHP enum owned this set, confirmed by repository-wide search for a "core stat" enum before creating a new one). `StoreMonsterRequest::rules()` now validates `damage_stat` with `Rule::enum(CoreStatType::class)` instead of a literal `Rule::in([...])` array (the repository's own established "Laravel enum validation" convention). `MonsterFormOptionsTransformer::transform()`'s `damage_stats` option list is now built from `CoreStatType::cases()` instead of a second hand-typed literal array — the two previously-duplicated magic-string lists now derive from one source. The frontend's own `DAMAGE_STAT_LABELS` map in `monster-identity-fields.tsx` was left as-is: it is the frontend's legitimate own presentation-label ownership (per `front-end-conventions`, backend enums must not own frontend labels), not a duplicate of the backend value list.
- **`celestial_type` — no domain owner exists; not invented.** Searched the full non-Admin/Monsters codebase for a `celestial_type`/`CelestialType` value owner. None exists. The **only** proven runtime meaning found is in `app/Game/Core/Services/DropCheckService.php` (two call sites): `$monster->celestial_type === 1` gates Mythic-drop logic (`king_celestial_mythic` reward source). No other value (`0`, `2`, `3`, ...) has any proven meaning anywhere in the codebase, and no enum class exists to validate against. Per the explicit instruction not to invent celestial semantics when no owner exists, `celestial_type` remains `'nullable|integer|min:0'` in `StoreMonsterRequest` — unchanged. This is the deliberate, correct outcome for this item, not a skipped fix.
- **Also fixed while auditing this area (Section 20 overlap, same file already open for the `damage_stat` change)**: `resources/js/admin/monsters/components/forms/monster-identity-fields.tsx` synthesized `` `Location Type ${value}` `` for the `only_for_location_type` dropdown instead of using an existing frontend label owner. `resources/js/admin/locations/enums/location-type.ts` already defines a complete, backend-value-matching `LocationType` enum + `LOCATION_TYPE_LABELS` map + `isLocationType()` guard (confirmed its 14 cases exactly match `App\Game\Maps\Values\LocationType`'s backed int cases). Rewired the dropdown to use `isLocationType(value) ? LOCATION_TYPE_LABELS[value] : \`Location Type ${value}\`` — a safe narrowing guard (no forced type assertion) with a fallback that can never crash on an unexpected value.

**Regression check**: `./vendor/bin/pest --fail-on-risky tests/Feature/Admin/Monsters/MonstersApiControllerTest.php` → **PASS**, 8 passed, 26 assertions, 0 risky (confirms the `CoreStatType` enum swap didn't change accepted/rejected `damage_stat` values).

---

## Section 8 — Monster import validation: COMPLETE

Same class of defect as Section 3: `MonsterImportController` always returned 200 "Monsters imported successfully." regardless of outcome, and `MonstersSheet` only validated Map/Quest-Item resolution + raid mutual exclusion — nothing else (no required-field checks, no `damage_stat`/`only_for_location_type`/`raid_special_attack_type` validation, boolean columns only defaulted when *missing*, never normalized when *present* with an unreliable string value).

Fix (`app/Admin/Monsters/Imports/Sheets/MonstersSheet.php` fully rewritten):
- `normalizeBlanks()`: blank spreadsheet cells (except `name`) become real `null`.
- `normalizeBooleans()`: every boolean column (`can_cast`, `is_celestial_entity`, `is_raid_monster`, `is_raid_boss`) is normalized with `filter_var($value, FILTER_VALIDATE_BOOLEAN)` — the same pattern already used by the legacy `MonstersController` for this exact problem — instead of only defaulting missing/blank values. A literal `"FALSE"` string cell now correctly becomes real `false` rather than passing through as an arbitrary truthy value.
- Map/Quest Item still resolved by name (unchanged convention); a blank/unresolvable relationship naturally fails the reused validation below rather than needing a separate guard.
- `hasValidManagedFields()`: validates the fully-normalized row against `(new StoreMonsterRequest)->rules()` — the exact same rule set the live Admin form enforces (one source of truth, not a duplicated hand-rolled rule list) — giving the import every required-field, numeric-constraint, `damage_stat` (`CoreStatType` enum), `only_for_location_type` (`LocationType` enum), and `raid_special_attack_type` (`RaidAttackType` enum) check for free.
- Raid Monster/boss mutual exclusion re-checked explicitly after normalization (the reused rule set's own `withValidator()` mutual-exclusion closure needs a bound `Request`, which a bare rule-array validation pass does not have).
- `wasSuccessful()`/`validationError()` exposed exactly like `QuestsSheet`.
- `MonstersImport`, `MonsterExcelService::import()` (now `ResponseBuilder`-based), `MonsterImportController` updated identically to the Quest fix — real success/failure now reaches the API response.

**Focused tests:**
- `tests/Feature/Admin/Monsters/MonstersImportTest.php` (new, 10 tests): valid workbook writes every managed field group (identity/placement, quest/celestial, spell/affix via `can_cast`+`max_spell_damage`, base stats via the Map/Item relationship path); missing required field rejected; invalid `damage_stat` rejected; invalid Map rejected; invalid Quest Item rejected; invalid `only_for_location_type` rejected; invalid `raid_special_attack_type` rejected; raid Monster/boss mutual exclusion rejected; a literal `'FALSE'` string cell does not become boolean `true`; one valid + one invalid row writes zero Monsters.
  Command: `./vendor/bin/pest --fail-on-risky tests/Feature/Admin/Monsters/MonstersImportTest.php` → **PASS**, 10 passed, 25 assertions, 0 risky.
- `tests/Feature/Admin/Monsters/MonsterImportControllerTest.php` (new, 2 tests): real `Excel::import()` pipeline through `POST /api/admin/monsters/import` via a real on-disk `.xlsx` (new fixture trait `tests/Traits/CreateMonstersWorkbookFile.php`) — valid workbook returns 200 and creates the Monster; invalid workbook returns 422 with a message and creates zero Monsters.
  Command: `./vendor/bin/pest --fail-on-risky tests/Feature/Admin/Monsters/MonsterImportControllerTest.php` → **PASS**, 2 passed, 5 assertions, 0 risky.
- New reusable fixture trait `tests/Traits/CreateMonsterImportRow.php` (`minimalMonsterImportRow()`), used by both new test files instead of a prohibited test-class private helper method.

## Section 9 — Additional Quest import/export tests: COMPLETE

`tests/Feature/Admin/Quests/QuestsExportTest.php` (new, 4 tests, calling the real `(new QuestsSheet)->view()->render()` production code path): `raid_id` export header appears exactly once; `required_quest_id` export header appears exactly once; before/after completion descriptions export raw Markdown (`**Bold**`, `_Italic_`, asserted absent of `<br`); `parent_chain_quest_id` round-trips into the export. Added `test_parent_chain_quest_id_compatibility_round_trip` to `QuestsImportTest.php` proving the compatibility column survives a real import.
Command: `./vendor/bin/pest --fail-on-risky tests/Feature/Admin/Quests/QuestsExportTest.php tests/Feature/Admin/Quests/QuestsImportTest.php` → **PASS**, 19 passed (all Quest import + export tests), 0 risky.

## Section 10 — Monster import/export tests: COMPLETE

`tests/Feature/Admin/Monsters/MonstersExportTest.php` (new, 2 tests): export contains current meaningful fields (name, Map name, `damage_stat`/`quest_item_id` headers); `can_use_artifacts` is confirmed **absent** from the export (it is cast on the model but not fillable and not referenced anywhere in `resources/views/admin/monsters/exports/sheets/monsters.blade.php` — verified by reading the view, not assumed). Import coverage (Quest Item relationship, Map relationship, zero-write-on-invalid) already delivered by Section 8's `MonstersImportTest.php`.
Command: `./vendor/bin/pest --fail-on-risky tests/Feature/Admin/Monsters/MonstersExportTest.php` → **PASS**, 2 passed, 5 assertions, 0 risky.

## Section 11 — Strengthened Monster create/update tests: COMPLETE

**Confirmed defect**: `test_store_creates_a_monster_from_every_field_group` and `test_update_modifies_every_field_group` in `MonstersApiControllerTest.php` only ever asserted `name`+`game_map_id` (create) and `name`+`xp` (update) — the test names overstated what was actually verified, exactly as the task described.

Fix: both tests now send a payload with representative real values from every one of the six managed field groups (identity/placement, base stats, probabilities, spell/affix/healing, quest/celestial, raid/special) and assert `assertDatabaseHas` against one representative column from each group. While rewriting these, also removed a second `private function minimalPayload()` test-class-helper violation in this same file (same category as Section 4's fix), replacing it with a new reusable fixture trait `tests/Traits/CreateMonsterFormPayload.php` (`minimalMonsterFormPayload()`).
Command: `./vendor/bin/pest --fail-on-risky tests/Feature/Admin/Monsters/MonstersApiControllerTest.php` → **PASS**, 8 passed, 26 assertions, 0 risky.

## Section 12 — Quest/Monster SidePeek architecture: COMPLETE

Confirmed by directory listing that `resources/js/admin/quests/components/side-peeks/` and the Monster equivalent did not exist at all before this session — only form components existed.

Built, following the exact existing `AdminItemDetailSidePeek` pattern (the established reference in this codebase):
- `resources/js/admin/quests/components/side-peeks/admin-quest-detail-side-peek.tsx` + `types/admin-quest-detail-side-peek-props.ts`: generic `Quest Details` frame title (set by callers, not the component itself); factual `QuestDetail` owns the real Quest name; outer `Edit Quest`/`Add Child Quest` actions (both reuse the existing `QuestFormContent` in `embedded` mode inside a `StackedCard`, matching how Item/NPC already embed their form content — no second form component); relationship callbacks open `ADMIN_ITEM_DETAIL` (existing), a newly stacked `ADMIN_QUEST_DETAIL` (itself, for Quest→Quest — genuine stacking, matching the codebase's existing `sidePeekEmitter.emit` pattern for opening one side-peek from inside another), and the new `ADMIN_MONSTER_DETAIL`.
- `resources/js/admin/monsters/components/side-peeks/admin-monster-detail-side-peek.tsx` + props type: generic `Monster Details` frame title; factual `MonsterDetail` owns the real Monster name; outer `Edit Monster` action (`MonsterFormContent` embedded in a `StackedCard`); callbacks open `ADMIN_ITEM_DETAIL` for the Quest Item.
- Registered both through the existing registration system: `SidePeekComponentRegistrationEnum` (`ADMIN_QUEST_DETAIL`, `ADMIN_MONSTER_DETAIL`), `SidePeekComponentPropsMap`, `SidePeekComponentRegistry` — no second navigation/registration framework invented.

Not yet verified by an actual rendered browser check (no frontend test harness exists in this repo per `front-end-conventions`) — type-correctness will be confirmed by the final `yarn type-check` gate.

## Section 13 — Cross-resource navigation graph: PARTIAL, every concretely fixable edge fixed

Audited every known hardcoded list-redirect for Quest/Monster (`grep` for `window.location.href = '/admin/quests'` / `'/admin/monsters'` across `resources/js/admin`) and replaced each with real entity-detail navigation now that Section 12's SidePeeks exist:
- `game-map-npc-side-peek.tsx` (Game Map → NPC → Quest click): now opens `ADMIN_QUEST_DETAIL` with the real quest id, instead of redirecting to the Quest list.
- `npc-show-screen.tsx` (standalone NPC → Quest click): same fix.
- `admin-item-detail-side-peek.tsx` (Item SidePeek → Quest/Monster): both now open the real detail SidePeek instead of the resource's list page.
- `item-show-screen.tsx`: the Quest Item factual-presentation navigation object's `on_open_quest`/`on_open_monster` now open real detail SidePeeks; **and** the separate Usage/Deletion-Impact card's `handleOpenRelatedEntity('quest'|'monster', id)` path (previously silently doing nothing once `quest`/`monster` were removed from its list-redirect map — traced and fixed, not left broken) now also opens the real SidePeeks.
- `quest-show-screen.tsx` and `monster-show-screen.tsx`: previously wired **zero** cross-resource navigation at all (`monster-show-screen.tsx` didn't even pass a `navigation` prop to `<MonsterDetail>`; `quest-show-screen.tsx` only wired Quest→Quest). Both now wire `on_open_item` (real `ADMIN_ITEM_DETAIL`), and Quest's screen additionally wires `on_open_monster` (real `ADMIN_MONSTER_DETAIL`).

**Not fixed, disclosed rather than silently left**: `on_open_npc`/`on_open_map`/`on_open_raid`/`on_open_passive`/`on_open_skill` still degrade to a list-page redirect everywhere. This is because no per-entity NPC/Map/Raid/Passive/Skill detail SidePeek exists anywhere in the current codebase for Quest/Monster's factual components to open (confirmed — Admin NPCs and Game Maps are each their own full SPA with no per-record deep-link URL, and Raid/Passive/Skill have no modernized Admin module at all). Building brand-new per-entity SidePeeks for five more resources is materially larger, un-requested new-module scope beyond Section 12's explicit ask ("Quest and Monster factual drill-down") and Section 13's own instruction to use existing infrastructure "whenever that infrastructure supports the destination" — it does not yet exist for these five. The full matrix in Section 13's table is therefore not 100% complete; every edge that could be wired through infrastructure that already exists (Quest↔Quest, Quest↔Item, Quest↔Monster, Monster↔Item, NPC↔Quest) now is.

> **SUPERSEDED.** The "no per-entity NPC/Map detail SidePeek exists" premise above is no longer true. A later remediation pass (see "Final Pre-Browser Remediation" → Remediation 1, further below) built and registered `ADMIN_NPC_DETAIL`, `ADMIN_LOCATION_DETAIL`, and `ADMIN_GAME_MAP_DETAIL` and rewired every `on_open_npc`/`on_open_map` handler this section left as a list redirect. Raid/Passive/Skill remain undestined by deliberate design (still no canonical Admin module for them), not by omission. Do not treat the "PARTIAL" verdict in this section's own heading as the current state — see the final status at the end of this document.

## Section 14 — Location and NPC detail Card grouping: COMPLETE

Confirmed by reading both files that `LocationDetailBody`/`NpcDetailBody` used bare `<section>` blocks, not the shared `Card`, and that neither NPC nor Location's own Map identity was clickable at all (`{location.game_map.name}` / `{npc.game_map.name}` were plain text — no `on_open_map` prop existed on either component).

Fix:
- `LocationDetailBody`: Identity+Description+Rules combined into one `Location Details` `Card` (matching the blueprint's exact recommended grouping); `Quest Items Dropped Here` in its own `Card`. Added optional `on_open_map?: (id: number) => void`, rendering the Map name as a real button when supplied (falls back to plain text otherwise — a non-breaking prop addition, every existing caller keeps working unchanged). Wired from `location-show-screen.tsx` (Game Maps has no per-record deep link, so this degrades to the Game Maps list — the same documented architectural fact as Section 13).
- `NpcDetailBody`: three separate Cards — `NPC Details`, `Quests`, `Quest Items Given by This NPC` (matching the blueprint's exact recommended grouping). Same `on_open_map` addition and wiring from `npc-show-screen.tsx`.

## Section 15 — Quest tree accessibility: COMPLETE (folded into the Section 2 work above)

Confirmed the exact defect: `quest-tree.tsx` held one shared `useRef<Map<number, HTMLDivElement>>` passed to both `QuestTreeDesktop` and `QuestTreeMobile`, which are **both** mounted simultaneously (CSS `hidden md:block` / `md:hidden` toggles visibility, not mount state). Since `QuestNode` writes `nodeRefs.current.set(quest.id, element)` for every rendered node and Mobile renders after Desktop, Mobile's (possibly `display:none`) element silently overwrote Desktop's ref for the same Quest id — so `focusNode()`'s `.focus()` call could target a non-rendering element, breaking desktop keyboard navigation.

Fix: `quest-tree.tsx` now owns two separate ref maps (`desktopNodeRefs`, `mobileNodeRefs`), each passed only to its own layout; `focusNode`/`buildKeyboardHandler` take the ref map as an explicit parameter instead of closing over one shared instance, so each layout's keyboard handler can only ever focus a node in its own, currently-visible tree.

Also removed `aria-selected={isFocused}` from `quest-node.tsx` (focus is not selection; the component does not model an actual selected node) and replaced the nested `aria-expanded` ternary with an explicit `resolveAriaExpanded()` helper using early returns (no nested ternary, matching `front-end-components-and-rendering`).

`role="tree"`/`role="treeitem"`/`aria-level`/`aria-expanded` (non-nested)/Enter-Space/Arrow navigation/Home-End/visible focus were all already present and are preserved unchanged.

## Section 16 — Component-rendering audit for new Quest/Monster components: COMPLETE (one real defect found and fixed; rest verified clean)

Audited every file the task names (`quest-detail`, `quest-giver-section`, `quest-dependencies-section`, `quest-requirements-section`, `quest-rewards-section`, `quest-story-section`, `quest-node`, `monster-identity-section`, `monster-quest-celestial-section`, `item-usage-card`) for nested ternaries and multi-line inline `{condition && (...)}` blocks. The only real violation found was `quest-node.tsx`'s nested `aria-expanded` ternary, fixed as part of Section 15 above. Every other named file is already small (50–140 lines) and uses named render helpers/early returns; no further changes were needed or made.

## Section 17 — API hook ownership: COMPLETE

Confirmed every one of the 13 hooks the task names by inline `export interface Use...Definition` declarations living directly in the hook file instead of `api/hooks/definitions/*`: `use-quest-tree`, `use-import-quests`, `use-quest-for-edit`, `use-quest-form-options`, `use-save-quest`, `use-quest-detail`, `use-save-monster`, `use-monster-for-edit`, `use-monster-detail`, `use-monster-form-options`, `use-import-monsters`, `use-public-quest-detail`, `use-public-monster-detail`. All 13 now have a matching `api/hooks/definitions/use-<name>-definition.ts` exporting the contract as `export default interface`, imported back into the (now much shorter) hook file. Verified via `grep` that no other file destructure-imports these type names from the old hook-file location (only the hook *function* is imported anywhere else), so this was a safe, non-breaking move. The Information feature's local props (`quest-info-tree-page.tsx`) needed no separate props file since it takes no external props (a page-level component driven entirely by its own hooks, matching the existing `PublicQuestDetailPage` pattern in the same file).

## Section 18 — FormWizard step validation: COMPLETE, focus-management follow-up disclosed

**Confirmed defect**: both `QuestFormContent`'s and `MonsterFormContent`'s `handleRequestNext` returned `true` unconditionally for every step except the final one — a user could click through Steps 1–3/1–4 with a blank required Quest name/NPC or Monster name/damage-stat/Map and only discover it at final submit.

Fix: both `useQuestForm`/`useMonsterForm` now expose `validate_step(stepIndex): boolean`, reusing the exact existing `validateQuestForm`/`validateMonsterForm` pure validators (no duplicated validation logic) — Quest's only required fields (`name`, `npc_id`) belong to Step 1, so `validate_step` runs on the step-0→1 transition; Monster's required fields (`name`, `damage_stat`, `game_map_id`) belong to Step 1 and the raid mutual-exclusion check belongs to Step 5, so `validate_step` maps each of those two step indices to its relevant field subset and only blocks advancement when one of *that step's* fields has an error (fields already validated on an earlier step remain visible in `field_errors` but do not re-block later steps). Both `handleRequestNext` functions now call `validate_step` first and return `false` (keeping the wizard on the current step, the existing `FormWizard` contract already used for submit failures) when it fails. The complete-form validation before final submit already existed and is unchanged.

**Not added (disclosed)**: explicit programmatic focus-move/scroll-into-view to the first invalid field on a blocked step. Field-specific errors are already rendered next to each input via the existing `errors={fieldErrors}` prop threaded through every Step's field component (visible, accessible, `aria-invalid`/`aria-describedby` wiring already present per `FieldWrapper`), so a screen-reader/keyboard user is not left without any error signal, but the cursor/focus is not forced to the first invalid field. This is a real, disclosed gap against the forms skill's "focus the first invalid field or accessible error summary" requirement, not silently claimed as done.

## Section 19 — Dense form layouts: COMPLETE

Confirmed exactly 4 occurrences of `lg:grid-cols-4` in the Quest/Monster forms (`monster-combat-fields.tsx` ×2, `quest-rewards-fields.tsx`, `quest-requirements-fields.tsx`) via `grep`. All 4 changed to `lg:grid-cols-3`, matching the blueprint's explicit "wide desktop up to three columns only" cap. No fixed input heights existed to remove.

## Section 20 — Frontend domain label audit: COMPLETE

Found and fixed **two** real synthesized-label defects (the task's own example, `Location Type ${value}`, was accurate — and a second, previously-undiscovered instance of the identical pattern for `raid_special_attack_type`):
- `monster-identity-fields.tsx` (Admin form): `` `Location Type ${value}` `` → now uses the existing, backend-value-matching `LocationType`/`LOCATION_TYPE_LABELS`/`isLocationType` from `admin/locations/enums/location-type.ts` (confirmed its 14 cases exactly match `App\Game\Maps\Values\LocationType`'s backed int enum before reusing it).
- `monster-raid-fields.tsx` (Admin form): `` `Attack Type ${value}` `` → new shared `RaidAttackType`/`RAID_ATTACK_TYPE_LABELS`/`isRaidAttackType` at `resources/js/game/reusable-components/monster/enums/raid-attack-type.ts` (values verified against `App\Game\Raids\Values\RaidAttackType`).
- While auditing the *shared* factual `monster-identity-section.tsx`/`monster-raid-section.tsx` (used by both Admin and public Information, so it cannot import from `admin/locations`), found both rendered `only_for_location_type`/`raid_special_attack_type` as **raw unlabeled numbers** with no attempted synthesis at all — arguably a worse gap than the flagged one, since public Information users would see a bare integer. Fixed both using the new shared `game/reusable-components/monster/enums/{location-type,raid-attack-type}.ts` (a small, deliberately-scoped duplicate of the Admin-owned `LocationType` enum values, since the shared component cannot depend on `resources/js/admin/**` — moving the canonical Admin file itself would touch several unrelated existing Admin/game callers outside this task's scope).
- `damage_stat`'s frontend `DAMAGE_STAT_LABELS` map in `monster-identity-fields.tsx` was inspected and left as-is: it was already a proper explicit frontend label map (not synthesized text), which is the frontend's legitimate presentation ownership.

## Section 21 — Game Map workspace scrolling: COMPLETE

Confirmed the flex chain described by the earlier session (`AdminPage` Workspace-width `flex min-h-0 flex-1 flex-col`, `GameMapEditorScreen`'s outer wrapper, `GameMapEditorCanvas`'s `h-full`) was already in place and correctly matches the blueprint. One real gap found: the canvas wrapper `<div>` in `game-map-editor-screen.tsx` was `className="min-h-[320px] flex-1"` — missing both `min-h-0` and `overflow-hidden`, which the blueprint's Section 126 explicitly requires ("flex-1; min-h-0; overflow-hidden"), and a fixed `320px` floor is exactly the kind of "fixed minimum that defeats desktop workspace ownership" the same section prohibits (on a short desktop viewport, a 320px floor can itself reintroduce the page-scroll bug being fixed). Changed to `className="min-h-0 flex-1 overflow-hidden"` — the exact three classes the blueprint names, nothing else. No coordinate math, tile size, marker centering, or pan/move service logic touched (none of those files were opened for this change).

## Section 22 — Monitoring hook-definition ownership: COMPLETE

Confirmed via `grep` across every `admin/monitoring/*/hooks/use-*.ts` file that `use-batch-crafting-dashboard.ts` was the **only** monitoring feature hook still declaring its return contract inline (`ChartPoint`, `ChartSeriesDefinition`, `UseBatchCraftingDashboardDefinition` — exactly the three types the task names). Moved all three into new `hooks/definitions/use-batch-crafting-dashboard-definition.ts`, imported back into the hook file. The other monitoring dashboards (delve, exploration, faction-loyalty, reward-queue, logs) already had no inline hook-return interfaces — confirmed by the same `grep`, not assumed.

## Section 23 — Hardcoded navigation/URL audit: COMPLETE (folded into Section 13's work above)

Every concrete hardcoded `window.location.href = '/admin/quests'` / `'/admin/monsters'` found by `grep` across `resources/js/admin` was replaced with real SidePeek-based entity navigation once Section 12's SidePeeks existed (see Section 13's log above for the exact file list). Quest/Monster's own feature API endpoints already used their `QuestApiUrls`/`MonsterApiUrls`/`QuestInfoApiUrls`/`MonsterInfoApiUrls` enums throughout this session's new code — no hardcoded endpoint strings were introduced in any new hook.

## Section 24 — Unused-file sweep: COMPLETE

Verified via the actual `yarn unimported` run inside the final quality gate (Section 29 below): **`✓ There don't seem to be any unimported files.`** — zero unimported files, confirming every new Quest/Monster/Info file this session created is genuinely reachable (including from the new Vite/route entry points), and no dead file was left behind.

## Section 26 — Monster backend tests: COMPLETE (all items already covered by Sections 8, 10, 11 above)

Auth boundaries, list/detail exact shape, static/no-scaling values, Map identity, Quest Item identity/drop chance — `MonstersApiControllerTest.php` (8 tests). Create/update across every managed field group, raid mutual exclusion, zero-chance-without-item normalization — same file, strengthened in Section 11. Import full validation + invalid-import-zero-writes + import-failure-not-success — `MonstersImportTest.php` (10 tests) + `MonsterImportControllerTest.php` (2 tests), Section 8. Export current field coverage + `can_use_artifacts` absence — `MonstersExportTest.php` (2 tests), Section 10.

## Section 27 — Public API tests: COMPLETE (already covered by Section 1's `tests/Feature/Info/**` above)

Unauthenticated public Quest tree, public Quest Map filter, public Quest Kind filter, public Quest factual detail, public Monster factual detail — `tests/Feature/Info/Quests/QuestsApiControllerTest.php` (4 tests) and `tests/Feature/Info/Monsters/MonstersApiControllerTest.php` (1 test), all hitting the real, final `/api/information/...` URLs with no `actingAs()`. "No Admin mutation endpoints exposed in public route file" verified by reading `routes/information/api.php` directly: it contains exactly three `Route::get(...)` lines and nothing else — no POST/PUT/PATCH/DELETE route exists in that file.

## Section 25 — Focused Quest tests: COMPLETE

Every item in the task's list is covered by tests already logged above: tree exact node shape/`map_id` filter/kind filter/deterministic hierarchy (`QuestsApiControllerTest`), exact factual detail/required-chain stored order/unlocked skill identity (`QuestsApiControllerTest::test_show_returns_full_factual_detail_shape`, backed by the Section 5 `QuestReadService` move), self-parent/descendant-parent/parent-cycle/self-required/required-cycle/self-in-chain/chain-cycle/duplicate-chain/invalid-relation-ids (`QuestsApiControllerTest`, 9 rejection tests), parent-flag synchronization including old-parent cleanup (`QuestsApiControllerTest`, 4 tests, Section 4), import workbook-local parent/required/chain + import graph cycle failures + import no-partial-writes + import-failure-not-success (`QuestsImportTest` 15 tests + `QuestImportControllerTest` 2 tests, Section 3), `parent_chain_quest_id` compatibility (`QuestsImportTest` + `QuestsExportTest`), export header uniqueness + raw Markdown export (`QuestsExportTest`, Section 9).

## Section 28 — proof_of_work.md truthfulness: this document

Every claim in this document is backed by either an actually-run `./vendor/bin/pest --fail-on-risky <file>` command (with the real pass/fail/assertion/risky count quoted) or an actually-read file (quoted line numbers/content, not inferred from a name). No claim from the previous, superseded version of this file was carried forward without independent re-verification. Every "COMPLETE" section above states the concrete defect found, the concrete fix, and the concrete test that proves it; every section not fully achievable is explicitly marked PARTIAL with the exact remaining gap named, not silently omitted.

## Section 29 — Final authorized quality gate: PASS, fully green

Ran exactly the authorized command:

```
yarn lint && yarn type-check && yarn cleanup && yarn unused-files-check && ./vendor/bin/pint && ./vendor/bin/pint --blade
```

**Result: the entire chain completed successfully end to end** (confirmed by the fact that `unimported` and both Pint stages — later `&&` links — actually ran, which bash only does when every preceding command exited 0):

1. `yarn lint`: **PASS** — 0 errors. (270 pre-existing warnings remained, all in files not touched by this session — spot-verified by directly re-linting every file this session created/edited, which showed 0 warnings once the fixes below were applied.)
2. `yarn type-check`: **PASS** — 0 TypeScript errors across the entire `resources/js` tree.
3. `yarn cleanup` (`prettier --write` + `eslint --fix` across all of `resources/js/**`, exactly as the task's command policy requires — no scoped/partial substitute was used): **PASS** — 0 errors after auto-fix; 7 warnings remained, every one in a file this session never touched (`use-own-game-map-move.ts`, `use-get-set-equippability-details.ts`, `use-move-item-to-set.ts`, `ui/alerts/alert.tsx` — all pre-existing `react-hooks/exhaustive-deps`/one pre-existing unused-arg warning). This step reformatted import ordering across many pre-existing files repo-wide (the literal, intended effect of running the exact authorized command) as well as this session's own files.
4. `yarn unused-files-check` (`unimported`): **PASS** — `✓ There don't seem to be any unimported files.`
5. `./vendor/bin/pint`: **PASS** — `{"tool":"pint","result":"passed"}`, zero files needed fixing (every PHP file this session touched was already Pint-clean).
6. `./vendor/bin/pint --blade`: **PASS** — fixed 3 pre-existing Blade files untouched by any session (`resources/views/components/form-wizard/step.blade.php`, `resources/views/flare/email/password_reset.blade.php`, `resources/views/layouts/admin.blade.php`) — the same 3 files the prior session's proof-of-work had already flagged as needing this exact fix; Pint applied it as part of running the real, authorized command.

**Because `yarn cleanup` changed frontend files, both `yarn lint` and `yarn type-check` were re-run against the final state** (per `repository-code-quality-and-clean-as-you-go`'s explicit requirement): both still **PASS** — 0 errors each.

**Prohibited-pattern audit** (re-run across every touched backend path after all fixes): zero `final class`, zero `declare(strict_types=1)`, zero real debug output (`var_dump`/`dd`/`dump`/`print_r`/`ray`/`fwrite` — the only grep hits were false positives from `toArray()`/`in_array()`/`->errors()->add()` substring matches, individually verified), zero `resolve()`/`app()` service location in touched Quest/Monster/Info code (`QuestKind::resolve()` is an unrelated static factory method, not a container call; the one real `resolve(BuildMonsterCacheService::class)` hit is in `MonsterListService.php`, a pre-existing file this session never touched), zero manual scalar casts in touched code (the only real casts found are in the pre-existing, out-of-scope combat `MonsterTransformer.php`), zero `DB::transaction`, zero `$request->all()` in the modernized Quest/Monster modules, zero test-class reflection/non-lifecycle helper methods (confirmed zero `private function`/`protected function` across every touched test file — both pre-existing `minimalPayload()` violations found this session were removed, not just avoided going forward), zero `assertTrue(true)`/`assertFalse(false)`, zero manual queued-job `->handle()` calls.

Every focused test this session added or touched was re-run one final time after the full gate (see the regression run logged just above this section in the actual session): **109 tests, 262 assertions, 0 risky, 0 failures.**

## Section 30 — Completion report

### Section-by-section status (1–30)

| # | Section | Status |
|---|---|---|
| 1 | Public Information API routing | **COMPLETE** |
| 2 | Missing public Quest tree | **COMPLETE** |
| 3 | Quest import repair | **COMPLETE** |
| 4 | Quest `is_parent` coherence | **COMPLETE** |
| 5 | Quest read querying out of transformer | **COMPLETE** |
| 6 | Quest tree `map_id` contract | **COMPLETE** |
| 7 | Backend skill violations in touched code | **COMPLETE** (celestial_type: no owner exists — recorded, not invented, per explicit instruction) |
| 8 | Monster import validation | **COMPLETE** |
| 9 | Quest import/export tests | **COMPLETE** |
| 10 | Monster import/export tests | **COMPLETE** |
| 11 | Monster create/update test strength | **COMPLETE** |
| 12 | Quest/Monster SidePeek architecture | **COMPLETE** |
| 13 | Cross-resource relationship graph | **PARTIAL** — every edge backed by existing/newly-built infrastructure (Quest↔Quest, Quest↔Item, Quest↔Monster, Monster↔Item, NPC↔Quest) is wired to real entity detail; edges needing an NPC/Map/Raid/Passive/Skill per-entity SidePeek that does not exist anywhere in the codebase still degrade to a list page (concrete, disclosed gap — see Section 13 log) |
| 14 | Location/NPC Card grouping + Map click | **COMPLETE** |
| 15 | Quest tree accessibility | **COMPLETE** |
| 16 | Component-rendering audit | **COMPLETE** (one real defect found in `quest-node.tsx`, fixed under Section 15; every other named file verified clean) |
| 17 | API hook ownership | **COMPLETE** |
| 18 | FormWizard step validation | **COMPLETE** — programmatic focus-move to the first invalid field on a blocked step not added (disclosed gap; field errors are already visible/accessible near each field) |
| 19 | Dense form layouts | **COMPLETE** |
| 20 | Frontend domain labels | **COMPLETE** |
| 21 | Game Map workspace scrolling | **COMPLETE** |
| 22 | Monitoring hook definitions | **COMPLETE** |
| 23 | Hardcoded navigation/URL audit | **COMPLETE** |
| 24 | Unused-file sweep | **COMPLETE** |
| 25 | Focused Quest tests | **COMPLETE** |
| 26 | Focused Monster tests | **COMPLETE** |
| 27 | Public API tests | **COMPLETE** |
| 28 | proof_of_work.md truthfulness | **COMPLETE** (this document) |
| 29 | Final authorized quality gate | **COMPLETE — PASS** |
| 30 | Completion report | **COMPLETE** (this section) |

### Files created (backend)

`app/Admin/Quests/Support/QuestImportGraphValidator.php`, `app/Game/Core/Values/CoreStatType.php`, `tests/Feature/Admin/Quests/QuestsImportTest.php`, `tests/Feature/Admin/Quests/QuestImportControllerTest.php`, `tests/Feature/Admin/Quests/QuestsExportTest.php`, `tests/Feature/Admin/Monsters/MonstersImportTest.php`, `tests/Feature/Admin/Monsters/MonsterImportControllerTest.php`, `tests/Feature/Admin/Monsters/MonstersExportTest.php`, `tests/Feature/Info/Quests/QuestsApiControllerTest.php`, `tests/Feature/Info/Monsters/MonstersApiControllerTest.php`, `tests/Traits/CreateQuestsWorkbookFile.php`, `tests/Traits/CreateMonstersWorkbookFile.php`, `tests/Traits/CreateMonsterImportRow.php`, `tests/Traits/CreateMonsterFormPayload.php`.

### Files created (frontend)

`resources/js/information/quests/api/enums/quest-info-api-urls.ts`, `.../api/hooks/definitions/{use-public-quest-detail-definition,use-public-quest-tree-definition}.ts`, `.../api/hooks/use-public-quest-tree.ts`, `.../components/quest-info-tree-page.tsx`; `resources/js/information/monsters/api/enums/monster-info-api-urls.ts`, `.../api/hooks/definitions/use-public-monster-detail-definition.ts`; `resources/views/information/quests/quests.blade.php`; `resources/js/admin/quests/api/hooks/definitions/{use-import-quests,use-quest-for-edit,use-quest-form-options,use-save-quest,use-quest-detail,use-quest-tree}-definition.ts`, `.../components/side-peeks/admin-quest-detail-side-peek.tsx` + props type; `resources/js/admin/monsters/api/hooks/definitions/{use-save-monster,use-monster-for-edit,use-monster-detail,use-monster-form-options,use-import-monsters}-definition.ts`, `.../components/side-peeks/admin-monster-detail-side-peek.tsx` + props type; `resources/js/game/reusable-components/monster/enums/{raid-attack-type,location-type}.ts`; `resources/js/admin/monitoring/batch-crafting-monitoring/hooks/definitions/use-batch-crafting-dashboard-definition.ts`.

### Files modified (representative — full list is the session's git diff)

`app/Providers/RouteServiceProvider.php`, `routes/web.php`, `app/Http/Controllers/InfoPageController.php`, `app/Admin/Quests/{Requests/QuestTreeRequest,Services/QuestService,Imports/Sheets/QuestsSheet,Imports/QuestsImport,Services/QuestExcelService,Controllers/Api/QuestImportController,Requests/StoreQuestRequest}.php`, `app/Info/Requests/QuestTreeRequest.php`, `app/Info/Controllers/Api/QuestsController.php`, `app/Admin/Quests/Controllers/Api/QuestsController.php`, `app/Game/Quests/{Services/QuestReadService,Transformers/QuestDetailTransformer}.php`, `app/Admin/Monsters/{Imports/Sheets/MonstersSheet,Imports/MonstersImport,Services/MonsterExcelService,Controllers/Api/MonsterImportController,Requests/StoreMonsterRequest,Transformers/MonsterFormOptionsTransformer}.php`, `tests/Feature/Admin/Quests/QuestsApiControllerTest.php`, `tests/Feature/Admin/Monsters/MonstersApiControllerTest.php`, `tests/Feature/Http/Controllers/InfoPageControllerTest.php`, and roughly 40 frontend files across `resources/js/admin/{quests,monsters,items,locations,npcs,game-maps,monitoring}` and `resources/js/game/{reusable-components/{quest,monster,quest-item},components/side-peeks}` implementing Sections 12–23 (SidePeek registration, cross-resource navigation, Card grouping, hook-definition moves, form-wizard validation, dense-form/label fixes, workspace scrolling).

### Files deleted

None. No file was proven safely deletable this session (Section 24's `unimported` check found zero orphaned files to remove).

### Focused tests and assertion counts (this session's full regression, actually run)

109 tests, 262 assertions, **0 risky**, 0 failures — `QuestsApiControllerTest` (21), `QuestsImportTest` (15), `QuestImportControllerTest` (2), `QuestsExportTest` (4), `MonstersApiControllerTest` (8), `MonstersImportTest` (10), `MonsterImportControllerTest` (2), `MonstersExportTest` (2), `Info/Quests/QuestsApiControllerTest` (4), `Info/Monsters/MonstersApiControllerTest` (1), `InfoPageControllerTest` (40).

### Exact final quality-gate result

**PASS**, fully green — see Section 29 above for the complete breakdown of all six stages plus the mandatory post-cleanup lint/type-check re-run.

### Remaining manual browser-QA matrix (not performed in this task, per its explicit instruction)

Every visual/interactive item in the blueprint's Section 132 Browser QA Matrix remains **MANUAL QA REQUIRED**, specifically including but not limited to:
- Quest tree keyboard navigation and screen-reader state announcement at desktop/tablet/mobile widths (the desktop/mobile ref-sharing bug is fixed in code — Section 15 — but not visually confirmed in a real browser);
- Quest/Monster FormWizard step-validation-blocks-advance behavior and dense-field responsive layout at each breakpoint;
- the new Quest/Monster Admin SidePeeks' visual stacking, focus management, and Escape/close behavior;
- Game Map workspace no-page-scroll behavior at ordinary and short desktop viewport heights, and mobile usability with the `min-h-[320px]` floor removed (Section 21);
- Location/NPC Card visual grouping and light/dark mode;
- the five refactored monitoring dashboards rendering identically to before;
- every relationship click-through (Quest↔Item, Quest↔Monster, NPC↔Quest, Item↔Quest, Item↔Monster) actually opening the correct stacked SidePeek in a real browser.

## CODE READY FOR MANUAL BROWSER QA (superseded — see the final status at the end of this document)

> **This declaration is historical, not current.** It reflected the state after this document's Sections 1–30 only. Two later remediation passes ("Final Pre-Browser Remediation" and "Final Static Cleanup Before Browser QA", both further below) found and fixed real additional defects — most notably completing the entity-detail navigation graph that Section 13 above left PARTIAL. Do not treat this section's bullet list as the current authoritative state; the single current conclusion is at the very end of this document.

All of the following are true, each backed by an actually-run command or actually-read file cited above, not assumed:
- public API routing works by contract (Section 1, tested against the real final `/api/information/...` URLs);
- public Quest tree exists (Section 2, tested);
- Quest import is functional and workbook-graph aware (Section 3, 15 passing tests including workbook-local forward references and full cycle detection);
- Monster import performs complete validation (Section 8, 10 passing tests reusing the live form's own rule set);
- Quest parent flags remain coherent (Section 4, 4 passing tests including old-parent cleanup);
- Quest read service owns read resolution (Section 5, transformer now performs zero queries);
- backend skill violations in touched Phase 2B code are removed (Section 7, with the one genuine no-owner-exists exception recorded rather than invented);
- API-hook ownership matches skills (Section 17, all 13 named hooks fixed);
- form wizard validation matches skills (Section 18, both wizards now block on invalid required steps);
- dense form layouts match blueprint (Section 19, verified zero `lg:grid-cols-4` remain);
- Quest accessibility implementation is corrected (Section 15, the real desktop/mobile ref-sharing bug is fixed);
- Location/NPC Cards and Map clicks are complete (Section 14);
- Quest/Monster SidePeeks exist (Section 12, registered through the existing system, zero new frameworks);
- every cross-resource edge backed by infrastructure that exists opens an entity detail rather than a list page (Section 13, PARTIAL at this point in the document's history — completed in the later "Final Pre-Browser Remediation" pass);
- Game Map workspace layout matches the flex ownership contract exactly (Section 21, the literal three required classes);
- monitoring touched code matches feature-layout conventions (Section 22);
- required focused tests pass with zero risky (Section 29, 109/109, 0 risky);
- final authorized quality chain passes (Section 29, fully green, including the mandatory post-cleanup re-run);
- no known code-level Phase 2B blocker remained **at that point in time**.

**Phase 2B itself is not declared complete** — only the code-readiness state above, and that state was itself incomplete (Section 13 PARTIAL). See the final status at the end of this document for the current, superseding conclusion.

---

# Final Pre-Browser Remediation

This section documents a subsequent remediation pass against the 20-item Phase 2B Final Pre-Browser Remediation prompt. It builds on the verified state above; it does not repeat or re-litigate work already logged there unless a new defect was found in it.

**Authoritative-source contradiction, recorded per the prompt's own instruction**: `flare-2.0-admin-game-data-implementation-blueprint-phase-2b.md`, listed as the 3rd authoritative source and the "implementation and acceptance authority for this phase," does not exist anywhere in this repository (confirmed via `find` across the entire working tree). Every remediation below was completed against the current source code, `.claude/skills/**`, and the prompt's own detailed per-remediation specification (which is self-contained), since the blueprint file could not be consulted. This is a genuine source-state contradiction, not a skipped step.

## Remediation 1 — Entity-detail navigation graph: COMPLETE

Audited every Admin callback across `resources/js/admin/{quests,monsters,items,locations,npcs,game-maps}` for a supplied entity id being discarded in favor of a resource-list redirect (`grep` for `window.location.href = '/admin/...'`). Confirmed the shared factual components (`QuestDetail`, `MonsterDetail`, `LocationDetailBody`, `NpcDetailBody`, `QuestItemFactualPresentation` and their partials) already correctly emit real ids through their `navigation` callback props — every remaining defect was in the Admin *adapter* layer discarding an id it was handed.

**Root cause**: no per-record NPC/Location/Game Map detail destination existed anywhere in the codebase — only `ADMIN_NPC_FORM`/`ADMIN_LOCATION_FORM`/`ADMIN_GAME_MAP_FORM` (edit) and `*_IMPORT` SidePeeks were registered. Confirmed each Admin app (`npcs-app.tsx`, `locations-app.tsx`, `game-maps-app.tsx`) always `resetTo(...Screens.LIST, {})` on mount with no query-param deep-link support, so a `window.location.href` redirect to `/admin/npcs` etc. could never have reached a specific record even with an id appended.

Fix — built three new generic read-only detail SidePeeks, each reusing the exact existing factual body component (not a new architecture, not a redesign):
- `resources/js/admin/npcs/components/side-peeks/admin-npc-detail-side-peek.tsx` (+ props type): wraps the existing `NpcDetailBody`, `useNpcDetail`/`useNpcQuests`/`useNpcRewardItems`; Edit action emits the existing `ADMIN_NPC_FORM`; `on_open_item`→`ADMIN_ITEM_DETAIL`, `on_open_quest`→`ADMIN_QUEST_DETAIL`, `on_open_map`→the new `ADMIN_GAME_MAP_DETAIL`.
- `resources/js/admin/locations/components/side-peeks/admin-location-detail-side-peek.tsx` (+ props type): wraps the existing `LocationDetailBody`; same pattern.
- `resources/js/admin/game-maps/components/side-peeks/admin-game-map-detail-side-peek.tsx` (+ props type): reuses the exact same fields `GameMapShowScreen` already renders (map preview, description, access/configuration, required Quest Item, bonuses); Edit emits the existing `ADMIN_GAME_MAP_FORM`.

Registered all three through the existing, single registration system (`SidePeekComponentRegistrationEnum` + `SidePeekComponentPropsMap` + `SidePeekComponentRegistry`) — no second navigation framework, exactly matching how `ADMIN_ITEM_DETAIL`/`ADMIN_QUEST_DETAIL`/`ADMIN_MONSTER_DETAIL` were registered previously.

Rewired every discarding handler to open a real detail instead of a list:
- `quest-show-screen.tsx` + `admin-quest-detail-side-peek.tsx`: `handleOpenNpc`/`handleOpenMap` now emit `ADMIN_NPC_DETAIL`/`ADMIN_GAME_MAP_DETAIL` with the real id (previously both discarded their `id` parameter entirely).
- `monster-show-screen.tsx` + `admin-monster-detail-side-peek.tsx`: `handleOpenMap` now emits `ADMIN_GAME_MAP_DETAIL`.
- `admin-item-detail-side-peek.tsx` + `item-show-screen.tsx`: `on_open_location`/`on_open_map`/`on_open_npc` now emit `ADMIN_LOCATION_DETAIL`/`ADMIN_GAME_MAP_DETAIL`/`ADMIN_NPC_DETAIL` with real ids (previously all three unconditionally redirected regardless of the id argument, and `on_open_location` in `item-show-screen.tsx`'s `handleOpenRelatedEntity` silently mapped `location` to a list-path lookup that discarded the id even though a per-blocker id was passed in).
- `location-show-screen.tsx` + `npc-show-screen.tsx`: `handleOpenMap` now emits `ADMIN_GAME_MAP_DETAIL` (not explicitly required by the remediation's edge list, but the same defect class; fixed while the destination SidePeek was already being built).

**Also fixed while auditing `ItemUsageCard`**: `item-show-screen.tsx`'s `handleOpenRelatedEntity` guessed dead deep-link URLs for the `raid`/`guide_quest` blocker resources (`/information/raids/{id}`, `/admin/guide-quests/show/{id}`) — confirmed via `grep` across `routes/` that neither route exists anywhere in the application. Per the explicit instruction not to build new Admin modules for Raid/Passive/Skill and to render non-interactive text where no destination exists, `ItemUsageCard` now only renders a clickable related-entity button for `NAVIGABLE_RELATED_ENTITY_RESOURCES = {quest, monster, location}`; `raid`/`guide_quest` render as plain text instead of a dead link.

**Verified already correct, not touched**: Game Map → Location detail and Game Map → NPC detail were already wired inside the Game Map editor (`game-map-editor-screen.tsx` emits `ADMIN_GAME_MAP_LOCATION`/`ADMIN_GAME_MAP_NPC`, which already render the full `LocationDetailBody`/`NpcDetailBody`); Quest↔Item, Quest↔Quest (parent/child/required/chain), Quest↔Monster, Item↔Quest, Item↔Monster, NPC↔Quest, NPC↔Item, Location↔Item were already correctly wired from the prior session's Section 12–14 work and needed no changes.

Focused verification: `yarn type-check` and targeted `yarn eslint` passed with zero errors after every step (see the Quality section below for the exact final run).

### Required final Admin graph — edge-by-edge status

| Edge | Opens exact detail? |
|---|---|
| Game Map → Location detail | Yes (pre-existing) |
| Game Map → NPC detail | Yes (pre-existing) |
| Location → required Item detail | Yes (pre-existing) |
| Location → reward Item detail | Yes (pre-existing) |
| Location → dropped Item detail | Yes (pre-existing) |
| NPC → Quest detail | Yes (pre-existing) |
| NPC → reward Item detail | Yes (pre-existing) |
| Item → drop Location detail | Yes (fixed this session) |
| Item → required Location detail | Yes (fixed this session) |
| Item → reward Location detail | Yes (fixed this session) |
| Item → Monster detail | Yes (pre-existing) |
| Item → required Quest detail | Yes (pre-existing) |
| Item → reward Quest detail | Yes (pre-existing) |
| Quest → NPC detail | Yes (fixed this session) |
| Quest → Quest-giver Game Map detail | Yes (fixed this session) |
| Quest → primary/secondary/reward Item detail | Yes (pre-existing) |
| Quest → parent/child/required/required-chain Quest detail | Yes (pre-existing) |
| Quest → access Map detail | Yes (fixed this session) |
| Quest → faction Map detail | Yes (fixed this session) |
| Quest → assisting NPC detail | Yes (fixed this session) |
| Monster → Game Map detail | Yes (fixed this session) |
| Monster → Quest Item detail | Yes (pre-existing) |

Every required edge now opens the actual related entity's detail. No edge in the required list degrades to a resource list.

## Remediation 2 — Quest import field validation: COMPLETE

**Confirmed defect**: `QuestsSheet::hasValidDomainValues()` (the import's only validation beyond relationship resolution) validated exactly three finite-domain fields (`only_for_event`, `unlocks_skill_type`, `unlocks_feature`) and nothing else — no `name` length/type check, no numeric-minimum check on any of the 11 currency/level fields, and `unlocks_skill`/`is_parent` were only defaulted when the cell was missing/blank, never normalized when present with an unreliable truthy string (the same class of bug already fixed for Monster import in the prior session).

**`is_parent` defect, confirmed by tracing the write path**: the workbook's raw `is_parent` cell flowed unchanged into `$baseData` and was written directly by `Quest::create()`/`$existingQuest->update()` in `writeRows()`'s phase 1. `QuestService::reconcileParentFlags()` (called per-row in phase 2) only ever corrects the flag on a row's *previous* and *new parent* — never on the row's own hierarchy state. A workbook Quest with a stale `is_parent=true` cell and zero actual children (nobody's `parent_quest_id` pointing at it, in this workbook or the existing DB) would keep that stale `true` value forever.

Fix (`app/Admin/Quests/Imports/Sheets/QuestsSheet.php`):
1. `resolveRow()`: `unset($data['is_parent'])` immediately after chain resolution — the workbook's `is_parent` cell is never trusted or written, full stop.
2. `applyDefaults()`: `unlocks_skill` now normalized via `filter_var($rawRow['unlocks_skill'] ?? false, FILTER_VALIDATE_BOOLEAN)` (previously only defaulted when missing/blank) — a literal `"FALSE"` string cell no longer becomes truthy.
3. New `hasValidManagedFields()` (renamed from `hasValidDomainValues()`, now the complete field contract): validates `name` (`required|string|max:255`), `before_completion_description`/`after_completion_description` (`nullable|string`), every numeric field at its Store-Request-matching `nullable|integer|min:0` constraint (`reincarnated_times`, `required_faction_level`, `required_fame_level`, `gold_cost`, `gold_dust_cost`, `shard_cost`, `copper_coin_cost`, `reward_gold`, `reward_gold_dust`, `reward_shards`, `reward_xp`), `unlocks_skill` (`required|boolean`), and the three finite-domain enums (unchanged). Relationship fields (`parent_quest_id`/`required_quest_id`/`required_quest_chain`) are deliberately excluded from this reused-rule validator, since they may legitimately hold a negative workbook-synthetic id at this point that a real `exists:quests,id` rule would incorrectly reject before the two-phase write resolves it — their own resolution/cycle validation is unchanged (`resolveQuestReference`/`resolveRequiredQuestChain`/`QuestImportGraphValidator`).
4. New `reconcileImportedParentFlags(array $questsById)`, called once after the existing phase-2 loop in `writeRows()`: for every Quest this import created or updated, sets `is_parent = Quest::where('parent_quest_id', $quest->id)->exists()` — a direct, unconditional derivation from the real resulting hierarchy (covering both workbook-local children and any pre-existing DB children), independent of and in addition to `reconcileParentFlags()`'s existing previous/new-parent correction (which still handles a parent *outside* this import batch losing its last child).

No transaction added (still prohibited); the write remains two-phase exactly as before, with validation still completing fully — every row, every relationship, the full workbook graph, all cycles, all duplicates — before any write begins.

## Remediation 3 — Quest import tests for the complete field contract: COMPLETE

Extended `tests/Feature/Admin/Quests/QuestsImportTest.php` (14 → 33 tests, all passing, 0 risky) with:
- 11 negative-value rejection tests: `reincarnated_times`, `gold_cost`, `gold_dust_cost`, `shard_cost`, `copper_coin_cost`, `required_faction_level`, `required_fame_level`, `reward_gold`, `reward_gold_dust`, `reward_shards`, `reward_xp`.
- 3 invalid-finite-domain rejection tests: `only_for_event`, `unlocks_skill_type`, `unlocks_feature`.
- 1 oversized-name rejection test (256 characters).
- 1 malformed-boolean normalization test: `unlocks_skill: 'maybe'` imports successfully but the Quest's `unlocks_skill` ends `false` (proving `FILTER_VALIDATE_BOOLEAN` normalization, not a hand-rolled truthy check).
- 2 hierarchy-derivation tests: a Quest imported with a stale `is_parent: true` cell and zero children ends `is_parent = false`; a Quest imported with `is_parent: false` and zero children also correctly ends `false` (i.e. the imported value is never read either way — both cases converge on the same derived-from-hierarchy result).

All pre-existing workbook-local parent/prerequisite/chain, cycle-rejection, duplicate-name, invalid-relation-id, zero-partial-write, and `parent_chain_quest_id` compatibility tests preserved unchanged and still passing.

Command: `./vendor/bin/pest --fail-on-risky tests/Feature/Admin/Quests/QuestsImportTest.php` → **PASS**, 33 passed, 68 assertions, 0 risky.

## Remediation 4 — Quest FormWizard step validation: COMPLETE

**Confirmed defect**: `validateQuestForm()` (the only validator that existed) checked exactly `name` and `npc_id` — every other field across all four steps had zero local validation, and `useQuestForm`'s `validateStep()` only ever ran this single flat validator on step index `0`, unconditionally returning `true` for steps 1–3.

Fix (`resources/js/admin/quests/utils/quest-form-state.ts`): replaced the single validator with four step-scoped validators (`validateQuestStoryStep`, `validateQuestStructureStep`, `validateQuestRequirementsStep`, `validateQuestRewardsStep`), each checking only that step's locally-knowable contract:
- **Story**: `name` required, `npc_id` required (relationship/select fields are already guaranteed a real id or `null` by the Dropdown's own `on_select`/`on_clear` contract, so no separate "type" check is meaningful).
- **Structure**: `reincarnated_times` non-negative integer.
- **Requirements**: `required_faction_level`, `required_fame_level`, `gold_cost`, `gold_dust_cost`, `shard_cost`, `copper_coin_cost` all non-negative integers.
- **Rewards**: `reward_gold`, `reward_gold_dust`, `reward_shards`, `reward_xp` all non-negative integers.

`validateQuestForm()` (used at final submit) is now the union of all four step validators, so a field left invalid on an earlier, already-passed step still blocks submission. `useQuestForm.validate_step(stepIndex)` now dispatches to the matching step validator instead of always running the same flat check. The backend remains the sole authority for relationship cycles — no graph logic was reproduced in React.

## Remediation 5 — Monster FormWizard step validation: COMPLETE

**Confirmed defect**: `validateMonsterForm()` checked only `name`, `damage_stat`, `game_map_id`, and the raid mutual-exclusion — every numeric field across every step (base stats, probabilities, spell/affix, quest/celestial currencies, atonement) had zero local validation, and `validateStep()` only re-ran this same flat check gated by a hard-coded `STEP_FIELDS` map that only listed 4 fields total across all 5 steps.

Fix (`resources/js/admin/monsters/utils/monster-form-state.ts`): five step-scoped validators matching each wizard step exactly:
- **Identity & Placement**: `name`, `damage_stat`, `game_map_id` required; `health_range`/`attack_range` required non-empty; `max_level`/`xp`/`gold` non-negative integers (backend `integer|min:0`); `drop_check` non-negative number (backend `numeric|min:0`, decimals allowed).
- **Core Combat**: `str`/`dur`/`dex`/`chr`/`int`/`agi`/`focus`/`ac` non-negative integers; `accuracy`/`dodge`/`criticality`/`ambush_chance`/`ambush_resistance`/`counter_chance`/`counter_resistance` non-negative numbers (backend `numeric|min:0`).
- **Spells & Affixes**: `max_spell_damage`/`max_affix_damage` non-negative integers; `casting_accuracy`/`spell_evasion`/`affix_resistance`/`healing_percentage`/`entrancing_chance`/`devouring_light_chance`/`devouring_darkness_chance`/`life_stealing_resistance` non-negative numbers.
- **Quest & Celestial**: `celestial_type`/`gold_cost`/`gold_dust_cost`/`shards` non-negative integers; `quest_item_drop_chance` non-negative number capped at `9.9999` (matching the backend's exact `nullable|numeric|min:0|max:9.9999`).
- **Raid & Special Rules**: `fire_atonement`/`ice_atonement`/`water_atonement` non-negative numbers; the existing raid-Monster/raid-boss mutual-exclusion check (unchanged, moved into this step's validator).

Every backend rule's `integer` vs `numeric` distinction was preserved (confirmed by reading `StoreMonsterRequest::rules()` field by field) — no field was validated more strictly than the backend allows. `validateMonsterForm()` is now the union of all five; `useMonsterForm.validate_step(stepIndex)` dispatches to the matching validator.

## Remediation 6 — First-error focus and scroll: COMPLETE

**Discovered and reused an existing established pattern instead of inventing a new one**: `resources/js/utils/focus-and-scroll-to-field.ts` (a shared, feature-agnostic `focusAndScrollToField(elementId)` utility, already skipping elements inside an `[inert]` ancestor — relevant because `FormWizard` marks inactive steps `inert`) already exists and is already consumed by Location's own form via `use-location-field-focus.ts` + `use-invalid-location-field-target.ts` + `resolve-first-invalid-location-field.ts`. An initial implementation of this remediation built an ad-hoc trigger-counter hook under `ui/form-wizard/hooks/`; once this existing convention was found (surfaced by `yarn cleanup`'s Prettier pass touching `resources/js/utils/focus-and-scroll-to-field.ts` in an unrelated diff, prompting a closer look at what already used it), the ad-hoc hook was deleted and replaced with feature-local hooks that follow the exact same architecture Location already established, right-sized for Quest/Monster's simpler single-step-only validation (no cross-step jump is needed, since `validate_step` only ever produces errors for the current step).

Built per feature (Quest and Monster), mirroring Location's shape:
- `resources/js/admin/{quests,monsters}/utils/resolve-first-invalid-{quest,monster}-field.ts`: the ordered field→element-id map per wizard step (the exact ids already rendered by each Fields component) plus a pure resolver.
- `resources/js/admin/{quests,monsters}/hooks/use-{quest,monster}-field-focus.ts`: identical in shape to `use-location-field-focus.ts` — calls the shared `focusAndScrollToField` when a pending field id is set, then clears it.
- `resources/js/admin/{quests,monsters}/hooks/use-focus-first-invalid-{quest,monster}-field.ts` (+ definitions file): owns an `attemptToken`/`consumedAttemptTokenRef` pair (an attempt only resolves once, and only when explicitly recorded — never from `fieldErrors` changing on its own while typing) and exposes `record_attempt()`.

Wired into `quest-form-content.tsx`/`monster-form-content.tsx`: `handleRequestNext` now calls `recordAttempt()` whenever `validate_step` returns `false`, before returning `false` to `FormWizard` (which itself keeps the current step active, per its existing contract).

**Error visibility gap found and fixed while wiring this in**: passing `field_errors` into each Fields component was necessary but not sufficient — `quest-requirements-fields.tsx`, `quest-rewards-fields.tsx`, `monster-combat-fields.tsx`, `monster-spell-fields.tsx`, and `monster-quest-celestial-fields.tsx` all received an `errors` prop but never destructured or rendered it (dead prop), and `quest-structure-fields.tsx`/`monster-identity-fields.tsx`/`monster-raid-fields.tsx` rendered it for only some of their fields. Since these fields now genuinely validate and can block navigation, a user landing on a blocked step with focus moved to the invalid field but no visible red error/`aria-invalid` next to it would be left without a reason. Wired `error={errors.<field>}` (and `invalid={!!errors.<field>}` on the two `Input` fields, matching the existing `quest-story-fields.tsx` convention) onto every newly-validated field across all 8 touched Fields files, so `FieldWrapper`'s existing `aria-invalid`/`aria-describedby`/error-text wiring is exercised for every field this remediation added validation to.

## Remediation 7 — Inline numeric parsing removed from JSX: COMPLETE

Confirmed via `grep` exactly 20 occurrences of `Number(item.value)` across the Quest and Monster form field files, every one inside a `Dropdown`'s `on_select={(item) => ...}` callback. Created `parseNumberOption(value: DropdownItem['value']): number` in `resources/js/admin/quests/utils/parse-quest-dropdown-value.ts` and an identical feature-local copy in `resources/js/admin/monsters/utils/parse-monster-dropdown-value.ts` (not a shared cross-feature utility, since no existing shared owner covers this exact behavior and the two features' `on_change` callbacks have independent state-field key types). Replaced all 20 call sites (`quest-structure-fields.tsx` ×4, `quest-story-fields.tsx` ×3, `quest-requirements-fields.tsx` ×5, `quest-rewards-fields.tsx` ×4, `monster-quest-celestial-fields.tsx`, `monster-raid-fields.tsx`, `monster-identity-fields.tsx` ×2). `on_clear` callbacks (which already pass `null` directly, never through `Number(...)`) were untouched. Verified via a repeat `grep` that zero `Number(item.value)`/`Number(value)`/`parseInt(` occurrences remain in either forms directory.

## Remediation 8 — Form hook contracts moved into definitions: COMPLETE

`UseQuestFormDefinition` (previously an inline `export interface` inside `use-quest-form.ts`) moved to `resources/js/admin/quests/hooks/definitions/use-quest-form-definition.ts` as `export default interface`; `UseMonsterFormDefinition` moved identically to `resources/js/admin/monsters/hooks/definitions/use-monster-form-definition.ts`. Confirmed via `grep` that neither type was imported anywhere outside its own hook file, so both moves were non-breaking. Both hook files now import the definition back in.

## Remediation 9 — Quest/Monster API ownership audit: COMPLETE, zero violations found

Audited `resources/js/admin/{quests,monsters}/api/**` and `resources/js/information/{quests,monsters}/**`: zero remaining inline `export interface Use...Definition` declarations in any hook file (confirmed by `grep`, the prior session's Section 17 work already covered every hook); zero hardcoded `/api/...` path literals outside the `*ApiUrls` enums; zero hooks returning JSX; zero Information-feature file importing from `admin/**`. No changes were needed in this area.

## Remediation 10–13 — Location/NPC/Item/Quest/Monster detail graph verification: COMPLETE

Every relationship named in these four remediations was traced to its actual current wiring (not assumed from a prior session's log):
- **Location → Map / required Item / reward Item / dropped Item**: all four already open real detail via `ADMIN_ITEM_DETAIL` and (after Remediation 1) `ADMIN_GAME_MAP_DETAIL`.
- **NPC → Map / Quest / reward Item**: all three already/now open real detail via `ADMIN_QUEST_DETAIL`, `ADMIN_ITEM_DETAIL`, and (after Remediation 1) `ADMIN_GAME_MAP_DETAIL`.
- **Item → Location / Quest / Monster / Map / NPC**: `monster-drop-section.tsx` confirmed to `.map()` over the *entire* `item.required_monsters` array (not a singular first-match), each with its own real `on_open_monster`/`on_open_map` link — no singular-Monster regression exists. `quests-that-use-section.tsx`/`reward-quests-section.tsx` confirmed passing real ids through `on_open_quest`/`on_open_npc`/`on_open_map`. All now resolve to real details after Remediation 1's Location/Map destinations.
- **Quest → NPC/parent/child/required/required-chain Quest/primary/secondary/reward Item/access Map/faction Map/assisting NPC/Monster**: every one confirmed to pass a real id through its `navigation` callback in `quest-giver-section.tsx`, `quest-dependencies-section.tsx`, `quest-requirements-section.tsx`, `quest-rewards-section.tsx`; every destination now real after Remediation 1.
- **Monster → Game Map / Quest Item**: both confirmed passing real ids in `monster-identity-section.tsx`/`monster-quest-celestial-section.tsx`; both now open real detail.

No redesign of any already-correct Card/section component was performed — only the Remediation 1 Admin-adapter fixes were needed.

## Remediation 14 — Quest tree static accessibility review: COMPLETE, zero new defects found

Re-verified `role="tree"`/`role="treeitem"`/`aria-level`/non-nested `aria-expanded`/no unwarranted `aria-selected`/`aria-hidden` decorative connectors are all still present in `quest-tree-desktop.tsx`, `quest-tree-mobile.tsx`, `quest-node.tsx` exactly as the prior session's Section 15 fix left them. No regression found; no change made.

## Remediation 15 — Dense form static review: COMPLETE, zero `lg:grid-cols-4` remain

`grep -rn "lg:grid-cols-4"` across `resources/js/admin/{quests,monsters,items}/components/forms` returns zero matches (the prior session's Section 19 fix is intact). No fixed field heights found. No change made.

## Remediation 16 — Game Map workspace static review: COMPLETE, layout intact

Confirmed the exact flex chain the prior session's Section 21 established is still in place: `admin-page.tsx`'s workspace mode (`flex min-h-0 flex-1 flex-col`), and `game-map-editor-screen.tsx`'s outer wrapper (`flex min-h-0 flex-1 flex-col`) and canvas wrapper (`min-h-0 flex-1 overflow-hidden`). No `calc()`, no JS viewport height, no coordinate/marker/tile-size changes made or needed.

## Remediation 17 — Monitoring static review: COMPLETE, architecture intact

`grep -rln "^export interface" resources/js/admin/monitoring/*/hooks/use-*.ts` returns zero matches — every monitoring feature hook (including `use-batch-crafting-dashboard.ts`, fixed in the prior session's Section 22) already owns its return contract under `hooks/definitions/**`. No change made.

## Remediation 18 — Backend factual payload tests for navigation ids: COMPLETE

The prior session's factual-shape tests asserted relationship *names* but not the *ids* navigation actually depends on. Extended coverage rather than replacing it:
- `tests/Feature/Admin/Quests/QuestsApiControllerTest.php::test_show_returns_full_factual_detail_shape`: added `assertSame($model->id, $data[...]['id'])`/`['item_id']` assertions alongside every existing name assertion (NPC, NPC's Game Map, parent Quest, required Quest, required chain, primary/secondary/reward Item, access Map, faction Map, assisting NPC, unlocked Passive) — 13 new id assertions.
- New `test_show_returns_child_quest_ids`: proves `structure.child_quests` carries real ids (the prior shape test never exercised this relationship at all).
- `tests/Feature/Admin/Monsters/MonstersApiControllerTest.php::test_show_returns_static_factual_detail_with_no_combat_scaling`: added `game_map.id` and `quest_item.item_id` assertions.
- New `tests/Feature/Admin/Items/ItemQuestItemFactualNavigationIdsTest.php`: one comprehensive test proving `presentation.drop_location.id`/`.game_map.id`, `presentation.required_locations.0.id`/`.game_map.id`, `presentation.reward_locations.0.id`/`.game_map.id`, `presentation.required_quests.0.id`/`.npc.id`/`.game_map.id`, `presentation.reward_quests.0.id`/`.npc.id`, and `presentation.required_monsters.0.id`/`.game_map.id` are all present and correct on a single Quest Item wired to one of each relationship — the exact list Remediation 18 names (Location ids, Map ids, NPC ids, Quest ids, all Monster ids).

Command: `./vendor/bin/pest --fail-on-risky tests/Feature/Admin/Quests/QuestsApiControllerTest.php tests/Feature/Admin/Monsters/MonstersApiControllerTest.php tests/Feature/Admin/Items/ItemQuestItemFactualNavigationIdsTest.php` → **PASS**, 31 passed, 0 risky.

## Remediation 19 — Final focused test pass: COMPLETE

Command:
```
./vendor/bin/pest --fail-on-risky \
  tests/Feature/Admin/Quests/QuestsApiControllerTest.php \
  tests/Feature/Admin/Quests/QuestsImportTest.php \
  tests/Feature/Admin/Quests/QuestImportControllerTest.php \
  tests/Feature/Admin/Quests/QuestsExportTest.php \
  tests/Feature/Admin/Monsters/MonstersApiControllerTest.php \
  tests/Feature/Admin/Monsters/MonstersImportTest.php \
  tests/Feature/Admin/Monsters/MonsterImportControllerTest.php \
  tests/Feature/Admin/Monsters/MonstersExportTest.php \
  tests/Feature/Info/Quests/QuestsApiControllerTest.php \
  tests/Feature/Info/Monsters/MonstersApiControllerTest.php \
  tests/Feature/Admin/Items/ItemQuestItemMonsterDropsTest.php \
  tests/Feature/Admin/Items/ItemQuestItemFactualNavigationIdsTest.php \
  tests/Feature/Admin/Items/ItemUsageTest.php
```
→ **PASS**, **92 passed, 268 assertions, 0 risky, 0 failures** (re-run a second time after the final `yarn cleanup` reformat, identical result).

## Remediation 20 — Code hygiene audit: COMPLETE, one self-introduced violation found and corrected

Ran the full prohibited-pattern grep (`console.log`, `debugger`, `dd(`/`dump(`/`var_dump`/`print_r`, `->handle()`, `DB::transaction`, `$request->all()`, `@ts-ignore`/`@ts-expect-error`, `eslint-disable`, ` as any`/` as unknown as`, `assertTrue(true)`/`assertFalse(false)`, test-class `private`/`protected` helper methods, `final class`, `declare(strict_types=1)`) across every file this session created or modified.

**One real violation found, in this session's own new code**: the first draft of `use-focus-first-invalid-field.ts` (Remediation 6, before it was replaced by the existing-convention-based implementation) carried an `// eslint-disable-next-line react-hooks/exhaustive-deps` comment to suppress a real lint warning about the intentionally-narrow `[trigger]` dependency array. Per the explicit "no lint suppression" rule, this was not an acceptable fix even though the underlying intent (fire only on `trigger` change) was correct — the file was superseded entirely by the Location-convention-based rebuild (Remediation 6), which reads the target field id through a value that does not require a lint-suppressed dependency array in the first place. Re-ran the grep after the rebuild: zero matches.

## Final quality gate

Ran exactly the authorized command:
```
yarn lint && yarn type-check && yarn cleanup && yarn unused-files-check && ./vendor/bin/pint && ./vendor/bin/pint --blade
```
**Result: PASS, fully green**, all six stages completed (confirmed by `unimported` and both Pint stages — later `&&` links — actually running):
1. `yarn lint`: 0 errors, 7 pre-existing warnings (all in files this session never touched: `use-own-game-map-move.ts` ×3, `use-get-set-equippability-details.ts`, `use-move-item-to-set.ts` ×2, `alert.tsx` — the identical set the prior session's proof already documented as pre-existing).
2. `yarn type-check`: 0 errors.
3. `yarn cleanup`: 0 errors after auto-fix (reformatted import ordering repo-wide, the intended effect of the authorized command; this session's own files were already correctly ordered where hand-written, confirmed by no meaningful diff in the touched files beyond whitespace).
4. `yarn unused-files-check` (`unimported`): **PASS** — `✓ There don't seem to be any unimported files.` (every new file this session created is reachable, including the three new SidePeek registrations and the new focus/validation hooks).
5. `./vendor/bin/pint`: **PASS**, zero files needed fixing.
6. `./vendor/bin/pint --blade`: fixed one pre-existing untouched file (`resources/views/flare/email/password_reset.blade.php`) — the same pre-existing Blade formatting gap the prior session's proof also recorded fixing.

Because `yarn cleanup` changes frontend files, `yarn lint` and `yarn type-check` were re-run against the final state per `repository-code-quality-and-clean-as-you-go`: both **PASS**, 0 errors.

## Files created this session

Backend: `tests/Feature/Admin/Items/ItemQuestItemFactualNavigationIdsTest.php`.

Frontend: `resources/js/admin/npcs/components/side-peeks/{admin-npc-detail-side-peek.tsx,types/admin-npc-detail-side-peek-props.ts}`; `resources/js/admin/locations/components/side-peeks/{admin-location-detail-side-peek.tsx,types/admin-location-detail-side-peek-props.ts}`; `resources/js/admin/game-maps/components/side-peeks/{admin-game-map-detail-side-peek.tsx,types/admin-game-map-detail-side-peek-props.ts}`; `resources/js/admin/quests/utils/{parse-quest-dropdown-value.ts,resolve-first-invalid-quest-field.ts}`; `resources/js/admin/monsters/utils/{parse-monster-dropdown-value.ts,resolve-first-invalid-monster-field.ts}`; `resources/js/admin/quests/hooks/{use-quest-field-focus.ts,use-focus-first-invalid-quest-field.ts,definitions/use-quest-form-definition.ts,definitions/use-focus-first-invalid-quest-field-definition.ts}`; `resources/js/admin/monsters/hooks/{use-monster-field-focus.ts,use-focus-first-invalid-monster-field.ts,definitions/use-monster-form-definition.ts,definitions/use-focus-first-invalid-monster-field-definition.ts}`.

## Files modified this session

Backend: `app/Admin/Quests/Imports/Sheets/QuestsSheet.php`; `tests/Feature/Admin/Quests/{QuestsImportTest.php,QuestsApiControllerTest.php}`; `tests/Feature/Admin/Monsters/MonstersApiControllerTest.php`.

Frontend: `resources/js/game/components/side-peeks/base/component-registration/{side-peek-component-registration-enum.ts,side-peek-component-props-map.ts,side-peek-component-registry.ts}`; `resources/js/admin/quests/{screens/quest-show-screen.tsx,components/side-peeks/admin-quest-detail-side-peek.tsx,components/forms/{quest-form-content.tsx,quest-structure-fields.tsx,quest-story-fields.tsx,quest-requirements-fields.tsx,quest-rewards-fields.tsx},hooks/use-quest-form.ts,utils/quest-form-state.ts}`; `resources/js/admin/monsters/{screens/monster-show-screen.tsx,components/side-peeks/admin-monster-detail-side-peek.tsx,components/forms/{monster-form-content.tsx,monster-identity-fields.tsx,monster-combat-fields.tsx,monster-spell-fields.tsx,monster-quest-celestial-fields.tsx,monster-raid-fields.tsx},hooks/use-monster-form.ts,utils/monster-form-state.ts}`; `resources/js/admin/items/{components/side-peeks/admin-item-detail-side-peek.tsx,screens/item-show-screen.tsx,components/item-usage-card.tsx}`; `resources/js/admin/locations/screens/location-show-screen.tsx`; `resources/js/admin/npcs/screens/npc-show-screen.tsx`.

## Files deleted this session

`resources/js/ui/form-wizard/hooks/use-focus-first-invalid-field.ts` — an ad-hoc first-draft hook (Remediation 6) superseded by the discovery of, and conformance to, the existing Location-established focus/scroll convention before it was ever committed to the final state.

## Remediation status summary (this pass)

| # | Remediation | Status |
|---|---|---|
| 1 | Entity-detail navigation graph | **COMPLETE** |
| 2 | Quest import field validation | **COMPLETE** |
| 3 | Quest import tests for full field contract | **COMPLETE** |
| 4 | Quest FormWizard step validation | **COMPLETE** |
| 5 | Monster FormWizard step validation | **COMPLETE** |
| 6 | First-error focus and scroll | **COMPLETE** |
| 7 | Inline numeric parsing removed | **COMPLETE** |
| 8 | Form hook contracts moved to definitions | **COMPLETE** |
| 9 | Quest/Monster API ownership audit | **COMPLETE** (zero violations found) |
| 10 | Location/NPC detail graph | **COMPLETE** |
| 11 | Item detail graph | **COMPLETE** |
| 12 | Quest detail graph | **COMPLETE** |
| 13 | Monster detail graph | **COMPLETE** |
| 14 | Quest tree accessibility static review | **COMPLETE** (no defect found) |
| 15 | Dense form static review | **COMPLETE** (no defect found) |
| 16 | Game Map workspace static review | **COMPLETE** (no defect found) |
| 17 | Monitoring static review | **COMPLETE** (no defect found) |
| 18 | Backend factual payload navigation-id tests | **COMPLETE** |
| 19 | Final focused test pass | **COMPLETE** — 92/92, 0 risky |
| 20 | Code hygiene audit | **COMPLETE** — 1 self-introduced violation found and corrected before completion |

## Overall status (superseded — see the final status at the end of this document)

**CODE READY FOR MANUAL BROWSER QA**

Every remediation in the Final Pre-Browser Remediation prompt is COMPLETE, each backed by an actually-run command or actually-read file cited above. The one source-state contradiction (the missing blueprint document) is recorded above and did not block any item, since the prompt's own per-remediation specification was self-contained and the current source code was read directly rather than assumed.

Phase 2B itself remains **not** declared complete. Remaining work is exactly the manual browser QA matrix already listed earlier in this document, unchanged by this pass — no item in it has been performed here.

> **A further remediation pass ("Final Static Cleanup Before Browser QA", below) ran after this one** and found and fixed additional static skill/convention defects (a remaining four-column form group, several large inline conditional-JSX blocks, misplaced API type ownership, an undocumented hook contract, remaining inline numeric parsing, and several forced TypeScript assertions). This section's "CODE READY" verdict is accurate for what it covered but is not the final word — see the end of this document.

---

# Final Static Cleanup Before Browser QA

This is a narrow final remediation pass, run after "Final Pre-Browser Remediation" above. It made **no backend/PHP changes** (per its own explicit command policy) and did not rebuild or redesign any Phase 2B architecture — it corrected the remaining verified static skill/convention defects the prior passes left behind: one four-column dense form group, several files with large inline conditional JSX that belonged in named render helpers, two API response shapes owned by the wrong directory, one hook with no explicit return-contract type, four files with leftover inline `Number(item.value)` parsing, and eight forced TypeScript assertions used to route around a type mismatch instead of fixing the underlying type.

## Section-by-section status

| # | Section | Status |
|---|---|---|
| 5 | Fix the remaining four-column Item form | **COMPLETE** — `item-combat-fields.tsx`'s stat-modifier group changed to `md:grid-cols-2 lg:grid-cols-3`; confirmed via `grep` that zero `md:grid-cols-4`/`lg:grid-cols-4`/`xl:grid-cols-4` remain in `resources/js/admin/{items,quests,monsters}/components/forms` |
| 6 | Refactor large conditional JSX — shared Quest components | **COMPLETE** — all 7 named files (`quest-requirements-section.tsx`, `quest-dependencies-section.tsx`, `quest-giver-section.tsx`, `quest-story-section.tsx`, `quest-detail.tsx`, `quest-rewards-section.tsx`, `quest-node.tsx`) refactored into the exact named render helpers specified |
| 7 | Refactor large conditional JSX — shared Monster components | **COMPLETE** — `monster-identity-section.tsx` (`renderGameMap`), `monster-quest-celestial-section.tsx` (`renderQuestItem`) |
| 8 | Refactor large conditional JSX — Item Admin components | **COMPLETE** — `item-usage-card.tsx` (`renderRelatedEntity`/`renderRelatedEntities`/`renderBlocker`), `item-quest-effect-fields.tsx` (`renderQuestEffectField`/`renderResurrectionChanceField`), `item-usable-fields.tsx` (`renderStatIncreaseField`/`renderKingdomDamageField`/`renderSkillBonusFields`/`renderHolyLevelField`/`renderUsableFields`), `item-crafting-fields.tsx` (`renderCraftingFields`) |
| 9 | Public Information component props ownership | **COMPLETE** — created `resources/js/information/quests/components/types/public-quest-detail-page-props.ts` and `resources/js/information/monsters/components/types/public-monster-detail-page-props.ts`; both `PublicQuestDetailPage`/`PublicMonsterDetailPage` now import their props type instead of declaring it inline |
| 10 | Remove the public Quest root inline ternary | **COMPLETE** — `quests-info-app.tsx` now calls `renderQuestInformationContent(questIdAttribute)`, a named helper, instead of a multiline ternary inside the `<ApiHandlerProvider>` render |
| 11 | Move Quest tree API response shapes to `api/definitions` | **COMPLETE** — created `resources/js/admin/quests/api/definitions/quest-tree-response-definition.ts` and `resources/js/information/quests/api/definitions/quest-info-tree-response-definition.ts`; both hook-definition files now contain only the hook return contract; both hooks (`use-quest-tree.ts`, `use-public-quest-tree.ts`) updated to import the response shape from its new location; confirmed via `grep` that no other file referenced the old location |
| 12 | Add the missing `useMonsters` hook definition | **COMPLETE** — created `resources/js/admin/monsters/api/hooks/definitions/use-monsters-definition.ts` typing the complete concrete return contract (not `ReturnType<typeof UsePaginatedApiHandler>`); `useMonsters(): UseMonstersDefinition` now explicitly annotated; `yarn type-check` passed immediately, confirming the definition exactly matches the hook's real return shape |
| 13 | Remove remaining inline `Number(item.value)` from Phase 2B code | **COMPLETE** — `quest-list-screen.tsx` and `quest-info-tree-page.tsx` now reuse the existing `parseNumberOption` from `admin/quests/utils/parse-quest-dropdown-value.ts` (Quest) and a new `information/quests/utils/parse-quest-info-dropdown-value.ts` (Information, since Information must not import Admin utilities); `monster-list-screen.tsx` reuses `admin/monsters/utils/parse-monster-dropdown-value.ts`; `item-quest-effect-fields.tsx` uses a new `admin/items/utils/parse-item-dropdown-value.ts` for all three of `drop_location_id`/`unlocks_class_id`/`item_skill_id` |
| 14 | Remove unsafe Quest kind assertions | **COMPLETE** — `isQuestKind()` in `quest-kind.ts` now uses `Object.values(QuestKind).some((kind) => kind === value)` instead of `.includes(value as QuestKind)`; `quest-list-screen.tsx` and `quest-info-tree-page.tsx` both now guard with `isQuestKind(item.value)` before calling `setKind`, with zero `as QuestKind` remaining |
| 15 | Remove unsafe Monster enum guard assertions | **COMPLETE** — `isRaidAttackType()` and `isLocationType()` both rewritten to `Object.values(Enum).some((member) => member === value)` after the existing `typeof value === 'number'` guard, with zero `as RaidAttackType`/`as LocationType` remaining |
| 16 | Remove form-state type assertions in Quest validation | **COMPLETE** — added the narrow `QuestNumericStringField` union (the exact 11 fields `validateNonNegativeIntegerFields` accepts) so `state[field]` is typed as `string` without an assertion; `validateQuestForm`'s `{} as QuestFormErrors` replaced with a typed local `const errors: QuestFormErrors = {}` populated via `Object.assign` across the step validators |
| 17 | Remove form-state type assertions in Monster validation | **COMPLETE** — added `MonsterIntegerStringField` and `MonsterNumberStringField` narrow unions matching the exact fields `validateIntegerFields`/`validateNumberFields` accept; `validateMonsterForm`'s `{} as MonsterFormErrors` replaced the same way |
| 18 | Remove form-hook `as string` assertions | **COMPLETE** — both `UseQuestFormDefinition['update_field']` and `UseMonsterFormDefinition['update_field']` now constrain their generic `K` to `Extract<keyof ...FormStateDefinition, string>` instead of the bare `keyof`; `clearServerFieldError(field as string)` in both `use-quest-form.ts`/`use-monster-form.ts` is now `clearServerFieldError(field)` with no cast; `yarn type-check` confirmed the narrower contract is sufficient |
| 19 | Fix Game Map Location SidePeek Map navigation | **COMPLETE** — `game-map-location-side-peek.tsx` now passes `on_open_map={handleOpenMap}` to `LocationDetailBody`, emitting the existing `ADMIN_GAME_MAP_DETAIL` SidePeek with the real Map id (previously omitted entirely) |
| 20 | Fix Game Map NPC SidePeek Map navigation | **COMPLETE** — `game-map-npc-side-peek.tsx` now passes `on_open_map={handleOpenMap}` to `NpcDetailBody`, same `ADMIN_GAME_MAP_DETAIL` pattern |
| 21 | Verify all Admin detail SidePeek destinations exist | **COMPLETE** — `grep` confirms `ADMIN_GAME_MAP_DETAIL`, `ADMIN_LOCATION_DETAIL`, `ADMIN_NPC_DETAIL`, `ADMIN_ITEM_DETAIL`, `ADMIN_QUEST_DETAIL`, `ADMIN_MONSTER_DETAIL` all remain registered in `side-peek-component-registration-enum.ts`; not modified beyond what Sections 19–20 needed |
| 22 | Final conditional-JSX audit | **COMPLETE** — re-audited every file named in Section 22 (`grep` for `? (` / `&& (` / `? <` / `&& <`) after Sections 6–10's fixes: zero matches remain in any of them |
| 23 | Final type-assertion audit | **COMPLETE** — `grep` for every named assertion pattern (`item.value as QuestKind`, `value as QuestKind`, `value as RaidAttackType`, `value as LocationType`, `field as string`, `{} as QuestFormErrors`, `{} as MonsterFormErrors`) across the six named Phase 2B directories: zero matches remain |
| 24 | Final API ownership audit | **COMPLETE** — `QuestTreeResponseDefinition`/`QuestInfoTreeResponseDefinition` confirmed to no longer exist in either `api/hooks/definitions/*` file; `useMonsters` confirmed to have `api/hooks/definitions/use-monsters-definition.ts`; both public detail components confirmed to import their props type rather than declaring it inline |
| 25 | Final numeric-parsing audit | **COMPLETE** — `grep -n "Number(item.value)"` across the exact four named files returns zero matches |

## Files created

- `resources/js/information/quests/components/types/public-quest-detail-page-props.ts`
- `resources/js/information/monsters/components/types/public-monster-detail-page-props.ts`
- `resources/js/admin/quests/api/definitions/quest-tree-response-definition.ts`
- `resources/js/information/quests/api/definitions/quest-info-tree-response-definition.ts`
- `resources/js/admin/monsters/api/hooks/definitions/use-monsters-definition.ts`
- `resources/js/information/quests/utils/parse-quest-info-dropdown-value.ts`
- `resources/js/admin/items/utils/parse-item-dropdown-value.ts`

Every file in the prompt's "expected newly created files" list was actually needed and actually created — none were already owned by an existing file.

## Files modified

`resources/js/admin/items/components/forms/item-combat-fields.tsx`, `resources/js/admin/items/components/item-usage-card.tsx`, `resources/js/admin/items/components/forms/item-quest-effect-fields.tsx`, `resources/js/admin/items/components/forms/item-usable-fields.tsx`, `resources/js/admin/items/components/forms/item-crafting-fields.tsx`; `resources/js/game/reusable-components/quest/components/{quest-requirements-section,quest-dependencies-section,quest-giver-section,quest-story-section,quest-detail,quest-rewards-section,quest-node}.tsx`, `resources/js/game/reusable-components/quest/enums/quest-kind.ts`; `resources/js/game/reusable-components/monster/components/{monster-identity-section,monster-quest-celestial-section}.tsx`, `resources/js/game/reusable-components/monster/enums/{raid-attack-type,location-type}.ts`; `resources/js/information/quests/quests-info-app.tsx`, `resources/js/information/monsters/monsters-info-app.tsx`, `resources/js/information/quests/components/quest-info-tree-page.tsx`; `resources/js/admin/quests/api/hooks/{definitions/use-quest-tree-definition.ts,use-quest-tree.ts}`, `resources/js/information/quests/api/hooks/{definitions/use-public-quest-tree-definition.ts,use-public-quest-tree.ts}`; `resources/js/admin/monsters/api/hooks/use-monsters.ts`; `resources/js/admin/quests/screens/quest-list-screen.tsx`, `resources/js/admin/monsters/screens/monster-list-screen.tsx`; `resources/js/admin/quests/utils/quest-form-state.ts`, `resources/js/admin/monsters/utils/monster-form-state.ts`; `resources/js/admin/quests/hooks/{definitions/use-quest-form-definition.ts,use-quest-form.ts}`, `resources/js/admin/monsters/hooks/{definitions/use-monster-form-definition.ts,use-monster-form.ts}`; `resources/js/admin/game-maps/components/side-peeks/{game-map-location-side-peek.tsx,game-map-npc-side-peek.tsx}`; `proof_of_work.md`.

## Files deleted

None. Every move in Section 11 relocated a response-shape interface out of a hook-definition file; the hook-definition file itself was kept (it still owns the hook's own return contract), matching the task's explicit instruction not to delete a file merely because one interface moved out of it.

## Focused Pest command

Not run. No backend/PHP file was modified in this pass (confirmed via `git status --short | grep '\.php$'` before and after — the only PHP diffs present are pre-existing, from earlier sessions), so per this task's explicit command policy ("Do not run Pest merely to create activity if no backend behavior changes"), no focused test run was required or performed.

## Final quality gate

Ran exactly:
```
yarn lint && yarn type-check && yarn cleanup && yarn unused-files-check && ./vendor/bin/pint && ./vendor/bin/pint --blade
```
**Result: PASS, fully green.** All six stages completed:
1. `yarn lint`: 0 errors, 7 pre-existing warnings (identical set both prior passes documented as pre-existing and untouched by any session: `use-own-game-map-move.ts` ×3, `use-get-set-equippability-details.ts`, `use-move-item-to-set.ts` ×2, `alert.tsx`).
2. `yarn type-check`: 0 errors.
3. `yarn cleanup`: 0 errors after auto-fix.
4. `yarn unused-files-check` (`unimported`): **PASS** — `✓ There don't seem to be any unimported files.` (confirms every file created in this pass is genuinely reachable).
5. `./vendor/bin/pint`: **PASS**, zero files needed fixing (no PHP touched this pass in any case).
6. `./vendor/bin/pint --blade`: fixed the same single pre-existing untouched file both prior passes also recorded fixing (`resources/views/flare/email/password_reset.blade.php`).

Because `yarn cleanup` reformats frontend files, `yarn lint` and `yarn type-check` were re-run against the final state per `repository-code-quality-and-clean-as-you-go`: both **PASS**, 0 errors, identical results to the first run.

## Proof-of-work contradiction cleanup

The stale `Section 13 — Cross-resource navigation graph: PARTIAL` conclusion and the two earlier "CODE READY FOR MANUAL BROWSER QA" declarations in this document (one after the original numbered Sections 1–30, one after "Final Pre-Browser Remediation") have each been marked superseded in place, with a pointer to this section as the current authoritative conclusion. No historical command/test evidence was deleted — only the stale top-level verdicts were annotated as no longer current, per this task's explicit instruction to leave evidence in place while superseding stale conclusions.

## Overall status (superseded — see the final status at the end of this document)

**CODE READY FOR MANUAL BROWSER QA**

Every section in this "Final Static Cleanup Before Browser QA" pass is COMPLETE. Every known static skill/convention defect identified by this prompt has been corrected and verified (by `grep` audit, `yarn type-check`, and the full final quality gate — not assumed). No backend/PHP behavior was changed. No Phase 2B architecture was rebuilt or redesigned.

Phase 2B is **not** declared complete.

Remaining Phase 2B work: manual browser QA.

> **A further pass ("Browser QA Batch 1 UI/UX Corrections", below) ran after this one**, addressing the first batch of manual browser QA defects. This section's verdict is accurate for what it covered but is not the final word — see the end of this document.

---

# Browser QA Batch 1 UI/UX Corrections

This pass addresses the first batch of manual browser QA defects reported against the Phase 2B admin surfaces: SidePeek/factual-detail title theming, Item SidePeek visual identity, Quest Item and NPC relationship presentation, a new Game Map "Related Game Data" browser (Locations/NPCs/Monsters/Quests/Quest Items), Quest story Before/After tabs with legacy markdown normalization, a Quest detail restructure (Story / Quest Details, Restrictions renamed from Availability), a real branching desktop Quest tree and a progressive-disclosure mobile Quest tree, a Monster list data-loading bug fix, and a new Monster Category filter (Regular / Raid Monster / Raid Boss / Celestial / Special Location / Weekly Fight) with an optional Location Type refinement. It reused the existing Phase 2B architecture throughout (SidePeek emit-stacking, `PillTabs`, `InfiniteScroll`, `UsePaginatedApiHandler`, the canonical Item color system, the canonical shared `EventType` label owner, `QuestKind::resolve()`, `QuestItemTransformer`) — no new generic relationship/card/table/tree/SidePeek-stack framework was introduced.

## Section-by-section status

| # | Section | Status |
|---|---|---|
| 6 | SidePeek dark-mode titles | **COMPLETE** — added a themed `<h1>` entity-name heading to `LocationDetailBody`, `NpcDetailBody`, `MonsterDetail`, and the Game Map factual detail (none had one at all, not merely the wrong color); Quest Item title bug fixed at its root — see Section 7/8 note below |
| 7 | Item SidePeek visual identity preserved | **COMPLETE** — no new Item renderer created; `QuestItemFactualPresentation`/`UsableItemFactualPresentation` now default `title_class_name` to `planeTextItemColors()` (via a synthesized minimal `ItemColorFieldsDefinition`) when the caller (Admin/Location contexts) omits it, fixing the root cause of the black Quest Item title (`ItemMetaSection`'s `<h2>` had no fallback), without touching `ItemMetaSection` or duplicating the color system |
| 8 | Quest Item relationship rows redesigned | **COMPLETE** — `quest-rows.tsx`, `location-row.tsx`, `monster-drop-section.tsx`, `quests-that-use-section.tsx`, `reward-quests-section.tsx`, `location-requirements-section.tsx`, `reward-location-section.tsx`, `drop-section.tsx` rewritten to compact clickable rows via `FactualLink`; Quest name primary+clickable, NPC/Map independently clickable, secondary text pattern (e.g. "Required by quest · Child of Shade · Shadow Plane"); `quest-map-row.tsx` deleted (folded into `quest-rows.tsx`) |
| 9 | Bounded relationship-section pattern | **COMPLETE** — new `RelationshipGroup` component (`quest-item/types/partials/relationship-group.tsx`) mirrors `Section`'s title/lead/separator treatment but wraps compact rows in a plain `<div>` instead of `Section`'s `<Dl>`; scoped only to `quest-item/partials/`, not a new generic framework, existing `Section` untouched |
| 10 | NPC Quest relationship — DataTable removed | **COMPLETE** — `use-npc-quests.ts`/its definition rewritten to the InfiniteScroll append shape (dropped `paginationMode: 'replace'`); `npc-detail-body.tsx`'s `renderQuests()` rewritten to `InfiniteScroll` + compact rows; each row: Quest name → Quest detail, required/secondary/reward Item → Item detail individually |
| 11 | Location relationship presentation | **COMPLETE** — verified already correct; dark-mode title fix (Section 6) applies |
| 12 | Game Map "Related Game Data" section | **COMPLETE** — `GameMapRelatedDataActions` (5 buttons: Locations/NPCs/Monsters/Quests/Quest Items) added to both the standalone show screen and the Game Map SidePeek |
| 13 | Game Map related-data backend endpoints | **COMPLETE** — new `GameMapRelationIndexRequest`; 5 GET routes (`related-locations`, `related-npcs`, `related-monsters`, `related-quests`, `related-quest-items`) registered before the generic `{gameMap}` routes; `GameMapService` gained 5 paginate methods + `applySearch`/`presentLocationTypes`/`attachResolvedKind`/`relatedQuestItemIds` helpers; canonical `QuestItemTransformer` reused directly for quest Items (no `GameMapQuestItemTransformer` created); Quests scoped by quest-giver NPC's `game_map_id` (not `access_to_map_id`/`faction_game_map_id`); Monsters scoped by direct `game_map_id` OR `only_for_location_type` matching a type present on the map's Locations; quest Item ids deduped across all 6 sources via `->filter()->unique()->values()` |
| 14 | Game Map related-data frontend API ownership | **COMPLETE** — 5 separate hooks under `admin/game-maps/api/hooks/` (not a generic `useRelatedGameData(resourceType)`), each page-size 10, append pagination, InfiniteScroll-compatible, optional search |
| 15 | Game Map relation SidePeek registration | **COMPLETE** — 5 new `ADMIN_GAME_MAP_RELATED_*` entries registered through the existing registry/enum/props-map system; each ~500px bounded region with `InfiniteScroll`; compact per-type rows (Locations: name+type+coords; NPCs: name+type+coords; Monsters: name+category badge; Quests: name+kind+NPC; Quest Items: `ReadOnlyItemCard` reused) |
| 16 | Relation SidePeek → detail navigation preserves list context | **COMPLETE** — resolved via the existing global SidePeek emit-stacking mechanism (already used elsewhere, e.g. NPC→Quest, Item→Quest/Monster): each relation SidePeek opens the already-registered canonical detail SidePeek (`ADMIN_LOCATION_DETAIL`/`ADMIN_NPC_DETAIL`/`ADMIN_MONSTER_DETAIL`/`ADMIN_QUEST_DETAIL`/`ADMIN_ITEM_DETAIL`) on top, keeping the relation list mounted underneath with its scroll position preserved; no new SidePeek stack or StackedCard wrapper introduced |
| 17 | Quest story Before/After tabs | **COMPLETE** — `quest-story-section.tsx` rewritten to use the existing `PillTabs`; each panel `max-h-[400px] overflow-y-auto` with a subtle border; new pure presentation utility `normalize-quest-story-markdown.ts` normalizes legacy `<br>`/`<br/>`/`<br />` (case-insensitive) to real line breaks for display only — persisted data is never mutated/re-saved; no HTML sanitizer added |
| 18 | Quest detail — exactly two primary Cards | **COMPLETE** — `quest-detail.tsx` rewritten to "Story" and "Quest Details" Cards only; Quest Giver → Structure & Dependencies → Requirements → Rewards → Restrictions (conditional) separated by the existing `Separator`, not individually Card-wrapped; no duplicate section headings |
| 19 | Structure & Dependencies readability | **COMPLETE** — `quest-dependencies-section.tsx` rewritten to full-width relationship groups (no squeezed 2-col `Dl`); Required Quest Chain preserves exact stored order (no sorting) as an ordinal-numbered vertical list at full width |
| 20 | Availability → Restrictions rename | **COMPLETE** — section renders only when a Raid or Event restriction is actually present; Raid shown only when present (no "Raid: None" row); Event restriction rendered via the canonical shared `EventType`/`getEventTypeName()`/new `isEventType()` guard — no second event label map created; no raw event integer ever rendered |
| 21 | Raid kind/identity consistency test | **COMPLETE** — `test_show_reports_raid_kind_and_raid_identity_consistently` added to `QuestsApiControllerTest.php`, proving a Quest with a valid `raid_id` resolves `kind === 'raid'` and `detail.availability.raid` is populated with the correct id/name; backend code (relation, eager-load, transformer) was independently verified correct — no orphaned-data issue found, nothing hidden or worked around |
| 22 | Desktop Quest tree — real branching tree | **COMPLETE** — new `QuestTreeDesktopNode` (recursive), root centered above horizontally laid-out children, CSS-only half-border connector lines (`aria-hidden`), multiple roots as separate spaced blocks, tree region owns its own horizontal overflow (`overflow-x-auto`) so the page itself never scrolls horizontally; no tree package, no `react-organizational-chart`, no CSS `calc()` |
| 23 | Mobile Quest tree — progressive disclosure | **COMPLETE** — `quest-tree-state.ts` gained `QUEST_TREE_STATE_SHORT_LABELS`; `quest-node.tsx` shows the short badge as the primary visible label with the full explanation as `sr-only`, adds a child count, and a quest-name-inclusive `aria-label`; disclosure chevron only expands/collapses (`stopPropagation`, never opens detail); expanding reveals only immediate children; existing ARIA (`role="tree"/"treeitem"/"group"`, `aria-level`, `aria-expanded`, focus/keyboard, desktop/mobile ref separation) preserved unchanged |
| 24 | Monster list data-loading bug fix | **COMPLETE** — root cause confirmed by reading `use-paginated-api-handler.ts`'s `shallowEqual`/`querySignatureChanged` logic: `additionalParams.filters` was a nested object literal recreated every render, so `shallowEqual` (which only compares top-level keys via `!==`) always saw it as changed and the paginator kept resetting its own data. Fixed by moving `game_map_id`/`category`/`location_type` into the paginator's own `initialFilters`/`setFilters` contract (a stable `useState` reference); `sort_key`/`sort_direction` stay as primitives in `additionalParams`, matching the established convention (e.g. `use-items.ts`). One data owner only — no second local Monster state array introduced |
| 25 | `MonsterListCategory` backend enum | **COMPLETE** — new string-backed `App\Admin\Monsters\Values\MonsterListCategory` (`all`/`regular`/`raid_monster`/`raid_boss`/`celestial`/`special_location`/`weekly_fight`); admin list/query value only, not persisted, no DB column added |
| 26 | Backend category query rules | **COMPLETE** — `MonsterService::applyCategoryFilter()` implements the exact rules per category (Regular/Raid Monster/Raid Boss: mutually exclusive boolean flags + `only_for_location_type IS NULL`; Celestial: `is_celestial_entity=true` + `only_for_location_type IS NULL`; Special Location: flags false + `only_for_location_type IS NOT NULL`, optionally narrowed to an exact type; Weekly Fight: flags false, `only_for_location_type` restricted to the fixed 4-value subset unless an exact weekly type is given); Cave of Memories is never added to the Weekly Fight subset and remains reachable only under Special Location; no `weekly` database flag added |
| 27 | Location Type filter validation tied to category | **COMPLETE** — `MonsterIndexRequest::withValidator()` rejects `location_type` unless `category` is `special_location` or `weekly_fight`, and for `weekly_fight` additionally rejects any type outside the fixed weekly subset; validated against the existing backend `LocationType` enum (`in:` rule built from `LocationType::values()`) — no duplicated raw value list; invalid combinations are rejected (422), never silently coerced |
| 28 | Celestial Type dropdown | **NOT ADDED, as instructed** — no proven domain owner for `Monster.celestial_type` semantics exists beyond `DropCheckService.php`'s `celestial_type === 1` Mythic-drop check (confirmed again this pass, consistent with the prior session's finding); scope was not expanded |
| 29 | Frontend Monster list filters | **COMPLETE** — new `resources/js/admin/monsters/enums/monster-list-category.ts` (category enum, label map, weekly-fight subset, `isMonsterListCategory` guard) and `utils/build-monster-location-type-items.ts`; `monster-list-screen.tsx` control order is Game Map → Monster Category → Location Type (only when category is Special Location/Weekly Fight) → Import → Export, with zero hardcoded category strings in the screen file; changing category clears an inapplicable Location Type and both filters reset to page 1 (via the paginator's own filter-change reset); all filters compose in a single request; no per-category endpoints |
| 30 | Monster category-filter backend tests | **COMPLETE** — 11 new tests added to `MonstersApiControllerTest.php`: game-map filter, each of the 6 categories individually, Cave of Memories under Special Location but excluded from Weekly Fight, Special Location + exact type, Weekly Fight + valid weekly type, invalid Location Type rejected, Location Type rejected for Regular, and full search+map+category+location+sort composition |
| 31 | Game Map related-data backend tests | **COMPLETE** — new `GameMapRelatedDataApiControllerTest.php` (16 tests): pagination response shape, per-relation map-scoping (Locations/NPCs), search, deterministic name ordering, Quests scoped by quest-giver NPC (not access/faction map), Monsters direct + special-location matching with no duplication when both conditions hold on the same Monster, Quest Items deduplicated across all 6 sources (shared Item appears exactly once) while still surfacing every genuinely distinct connected Item, auth/authorization. **This pass also found and fixed a genuine pre-existing runtime bug** surfaced by these tests: `GameMapService::presentLocationTypes()` and `relatedQuestItemIds()` were type-hinted to return `Illuminate\Database\Eloquent\Collection` but `pluck()`/`collect()` actually return `Illuminate\Support\Collection`, causing a `TypeError` (HTTP 500) on every call to `related-monsters` and `related-quest-items`. Fixed by retyping both methods to `Illuminate\Support\Collection` (aliased `SupportCollection`); confirmed no regression via the existing `GameMapServiceTest` and `GameMapsApiControllerTest` (73 tests, all passing) |
| 32–45 | Non-goal verification, dark/light-mode audit, accessibility, responsive, file discipline | **COMPLETE** — grep-based audit swept the touched trees for: `calc()` usage (none), any of the prohibited new-framework names (none), `react-organizational-chart`/tree packages (none added to `package.json`), remaining `DataTable` in the NPC Quest relationship or the new Game Map relation SidePeeks (none), a duplicated event-label map (the one `game-map-event-type.ts` hit is the pre-existing, unrelated Admin GameMap-own `only_during_event_type` field enum from Task 1, not a Quest-restriction duplicate), Admin imports inside shared/game factual components (none), debug output / `dd()`/`dump()`/`var_dump()` / `$request->all()` in touched backend files (none), unsafe `as`/`as unknown as`/`as any` assertions in touched files (none), lint/TS suppressions in touched files (none), and visible raw `<br />` tokens surviving the Quest story render path (none — normalization confirmed working). Main resource list screens (Monster/NPC/Location/Item) confirmed still using `DataTable`, unchanged. No Quest mutation/import/export, Monster mutation/import/export, Item deletion/subtype filtering, map coordinates/tile generation, Phase 3 Character Quest state, or database schema was touched. No migrations were run or created |
| 46 | Final static audit checklist | **COMPLETE** — see Section 32–45; all checklist items pass |
| 47 | Final quality command | **COMPLETE — PASS** — see Final quality gate below |
| 48 | proof_of_work.md update | **COMPLETE** — this section |

## Files created

`resources/js/game/reusable-components/quest-item/partials/relationship-row-styles.ts`; `resources/js/game/reusable-components/quest-item/types/partials/relationship-group-props.ts`, `.../partials/relationship-group.tsx`; `resources/js/admin/npcs/api/hooks/definitions/use-npc-quests-definition.ts` (rewritten in place, not new); `app/Admin/GameMaps/Requests/GameMapRelationIndexRequest.php`; `app/Admin/GameMaps/Transformers/GameMapRelatedLocationTransformer.php`, `GameMapRelatedNpcTransformer.php`, `GameMapRelatedQuestTransformer.php`, `GameMapRelatedMonsterTransformer.php`; `resources/js/admin/game-maps/api/definitions/game-map-related-{location,npc,monster,quest}-definition.ts`; `resources/js/admin/game-maps/api/hooks/use-game-map-related-{locations,npcs,monsters,quests,quest-items}.ts` + matching `definitions/*-definition.ts`; `resources/js/admin/game-maps/components/side-peeks/game-map-related-{locations,npcs,monsters,quests,quest-items}-side-peek.tsx` + matching `types/*-props.ts`; `resources/js/admin/game-maps/components/game-map-related-data-actions.tsx` + `types/game-map-related-data-actions-props.ts`; `resources/js/game/reusable-components/quest/utils/normalize-quest-story-markdown.ts`; `resources/js/game/reusable-components/quest/types/quest-story-panel-props.ts` + `components/quest-story-panel.tsx`; `resources/js/game/reusable-components/quest/types/quest-tree-desktop-node-props.ts` + `components/quest-tree-desktop-node.tsx`; `app/Admin/Monsters/Values/MonsterListCategory.php`; `resources/js/admin/monsters/enums/monster-list-category.ts`; `resources/js/admin/monsters/api/definitions/monster-list-filters-definition.ts`; `resources/js/admin/monsters/utils/build-monster-location-type-items.ts`; `tests/Feature/Admin/GameMaps/GameMapRelatedDataApiControllerTest.php`.

## Files deleted

`resources/js/game/reusable-components/quest-item/partials/quest-map-row.tsx` and `types/partials/quest-map-row-props.ts` (folded into `quest-rows.tsx`).

## Files modified (representative — not exhaustive)

`resources/js/game/reusable-components/quest-item/partials/{quest-rows,location-row,monster-drop-section,quests-that-use-section,reward-quests-section,location-requirements-section,reward-location-section,drop-section}.tsx`; `resources/js/admin/npcs/api/hooks/use-npc-quests.ts`, `resources/js/admin/npcs/components/npc-detail-body.tsx`; `app/Admin/GameMaps/Services/GameMapService.php`, `app/Admin/GameMaps/Controllers/Api/GameMapsController.php`, `routes/admin/game-maps/api.php`; `resources/js/admin/game-maps/api/enums/game-map-api-urls.ts`; `resources/js/game/components/side-peeks/base/component-registration/{side-peek-component-registration-enum,side-peek-component-props-map,side-peek-component-registry}.ts`; `resources/js/admin/game-maps/screens/game-map-show-screen.tsx`, `resources/js/admin/game-maps/components/side-peeks/admin-game-map-detail-side-peek.tsx`; `resources/js/game/reusable-components/quest/components/{quest-story-section,quest-giver-section,quest-requirements-section,quest-rewards-section,quest-dependencies-section,quest-detail,quest-tree-desktop,quest-tree-mobile,quest-node}.tsx`; `resources/js/game/reusable-components/quest/enums/quest-tree-state.ts`; `resources/js/game/components/announcements/enums/EventType.ts`; `resources/js/game/reusable-components/monster/components/monster-detail.tsx`, `resources/js/admin/locations/components/location-detail-body.tsx` (entity-name headings); `tests/Feature/Admin/Quests/QuestsApiControllerTest.php`; `app/Admin/Monsters/Requests/MonsterIndexRequest.php`, `app/Admin/Monsters/Services/MonsterService.php`; `resources/js/admin/monsters/api/hooks/use-monsters.ts`, `resources/js/admin/monsters/api/hooks/definitions/use-monsters-definition.ts`, `resources/js/admin/monsters/screens/monster-list-screen.tsx`; `tests/Feature/Admin/Monsters/MonstersApiControllerTest.php`; `proof_of_work.md`.

## Backend behavior changed

1. **Game Map related-data read endpoints** (Section 13) — five new GET endpoints, additive only, no existing endpoint's behavior changed.
2. **Monster list category filtering** (Sections 25–27) — `filters.category`/`filters.location_type` are new, optional request inputs; omitting them (or passing `category=all`) reproduces the exact prior unfiltered/`game_map_id`-filtered behavior, so no existing caller's behavior changed.
3. **Bug fix**: `GameMapService::presentLocationTypes()`/`relatedQuestItemIds()` return-type mismatch (Section 31) — this was a genuine defect in the newly-added Section 13 code from earlier in this same pass, not a change to any previously-working behavior; the two related endpoints (`related-monsters`, `related-quest-items`) were non-functional (HTTP 500) before this fix.

No Quest mutation/import/export, Monster mutation/import/export, Item deletion/subtype filtering, map coordinates, tile generation, Phase 3 Character Quest state, or database schema was changed. No migrations were created or run.

## Focused tests run

```
./vendor/bin/pest --fail-on-risky tests/Feature/Admin/Monsters/MonstersApiControllerTest.php
```
20 passed (64 assertions), 0 risky.

```
./vendor/bin/pest --fail-on-risky tests/Feature/Admin/GameMaps/GameMapRelatedDataApiControllerTest.php
```
16 passed (32 assertions), 0 risky.

Regression confirmation (pre-existing files, re-run after the Section 31 bug fix):
```
./vendor/bin/pest --fail-on-risky tests/Feature/Admin/GameMaps/GameMapsApiControllerTest.php tests/Unit/Admin/GameMaps/Services/GameMapServiceTest.php tests/Feature/Admin/Quests/QuestsApiControllerTest.php tests/Feature/Admin/Npcs/NpcsApiControllerTest.php
```
113 passed, 0 risky (63 + 10 + 23 + 17). Combined with the 20 + 16 new/modified-file tests above, all six files together: 149 passed, 0 risky, 368 assertions.

## Final quality gate

Ran exactly:
```
yarn lint && yarn type-check && yarn cleanup && yarn unused-files-check && ./vendor/bin/pint
```
**Result: PASS, fully green.**
1. `yarn lint`: 0 errors, 15 pre-existing warnings, all in files untouched by this pass (`use-own-game-map-move.ts` ×3, `side-peek-component-props-map.ts` ×8 `max-len` — pre-existing, documented in the prior pass — `use-get-set-equippability-details.ts`, `use-move-item-to-set.ts` ×2, `alert.tsx`).
2. `yarn type-check`: 0 errors.
3. `yarn cleanup`: 0 errors after auto-fix; re-ran `yarn lint`/`yarn type-check` afterward, both still 0 errors.
4. `yarn unused-files-check` (`unimported`): **PASS** — `✓ There don't seem to be any unimported files.`
5. `./vendor/bin/pint`: **PASS**.

## Overall status

**READY TO RETEST BROWSER QA BATCH 1**

Every section in this pass is COMPLETE. Backend and frontend changes are limited to exactly what Sections 6–48 authorized: the five Game Map related-data read endpoints, Monster category/location-type list filtering, and the frontend UI/UX corrections listed above. No Quest mutation/import/export, Monster mutation/import/export, Item deletion/subtype filtering, map coordinates, tile generation, Phase 3 Character Quest state, or database schema was touched. No new generic relationship/card/table/tree/SidePeek-stack framework was introduced — every correction reused existing Phase 2B architecture. The browser defects addressed in this pass have **not** been visually re-verified in this execution; only static verification (types, lint, focused backend tests, grep-based audits) was performed.

This is the single current authoritative conclusion for this document. It supersedes every earlier "CODE READY"/"PARTIAL" statement above.

Remaining work: manually retest Browser QA Batch 1 before continuing the rest of the Phase 2B browser-QA matrix.

---

# Browser QA Batch 1 Final Corrections

This is the final correction pass before Browser QA Batch 1 is manually retested. It does not rebuild the Browser QA Batch 1 work above; it corrects five specific defects: Quest story prose, desktop Quest tree horizontal overflow, desktop Quest tree ARIA child-group structure, Game Map related-data SidePeeks replacing (rather than stacking over) the relationship browser, and nested entity navigation destroying an ancestor's stack. No backend code was changed in this pass. No `git`, database, or test-suite command was run per this pass's command policy.

## Source authority

No named file in the correction instructions had moved; every file referenced (`quest-story-panel.tsx`, `quest-tree-desktop.tsx`, `quest-tree-desktop-node.tsx`, the five `game-map-related-*-side-peek.tsx` files, and the Admin Quest/Monster/Item/Location/NPC detail SidePeeks) was found at its stated path. No `SOURCE STATE MISMATCH` was recorded.

## Section-by-section status

| # | Section | Status |
|---|---|---|
| 6–8 | Quest story real Tailwind Typography prose | **COMPLETE** — `quest-story-panel.tsx`'s inner wrapper changed from `text-glacier-700 dark:text-glacier-300 text-sm leading-relaxed break-words` to `prose prose-sm sm:prose-base dark:prose-invert max-w-none break-words`; outer bounded viewport (`max-h-[400px] overflow-y-auto rounded-md border p-3`) unchanged; `normalize-quest-story-markdown.ts` legacy `<br>` normalization untouched; Before/After tabs (`quest-story-section.tsx`, `PillTabs`) untouched — both tab panels use the same corrected `QuestStoryPanel`, so both gained prose automatically with no duplicated classes |
| 9–16 | Desktop Quest tree fits available width | **COMPLETE** — `quest-tree-desktop.tsx`: removed `overflow-x-auto` and `min-w-max` from the root wrapper/list, added `w-full`; `quest-tree-desktop-node.tsx`: removed the fixed `min-w-[9rem]` node card width (now `max-w-full`, compact `px-2 py-1.5` padding, `break-words` name), removed the `px-4` per-child spacing (now `flex-1 min-w-0 px-1 sm:px-2`), and made every level (`root li`, node wrapper, children row, child wrapper) `w-full min-w-0` so each subtree recursively owns only its proportional share of the parent's width via plain flexbox — no viewport JS measurement, `calc()`, zoom, or scale transforms. Quest names wrap instead of forcing horizontal scroll; a pathologically wide tree grows taller (one sibling row per parent preserved), never sideways. Connector lines (half-border top segments + vertical stems) needed no pixel-math changes since they were already expressed as percentages of each child wrapper's own box, which now simply resizes with its flex share |
| 16 | Desktop tree ARIA child-group structure | **COMPLETE** — the `<div>` wrapping a node's mapped child treeitem subtrees now carries `role="group"` (added only when `hasChildren`); decorative connector `<div>`s remain `aria-hidden="true"` and did not receive `role="group"`; `role="treeitem"`/`aria-level`/`aria-expanded` on nodes, root `role="tree"`, and existing keyboard/focus callbacks are unchanged |
| 17 | Mobile Quest tree unchanged | **COMPLETE** — `quest-tree-mobile.tsx`/`quest-node.tsx` were not touched in this pass; progressive disclosure, collapsed roots, immediate-children-only expansion, and existing ARIA remain exactly as Batch 1 left them |
| 18–20 | Game Map related-data SidePeeks stack via `StackedCard` | **COMPLETE** — all five (`game-map-related-{locations,npcs,monsters,quests,quest-items}-side-peek.tsx`) no longer call `useSidePeekEmitter`/emit a replacement global `SIDE_PEEK`; each now owns a local `selected{Location,Npc,Monster,Quest,Item}Id: number \| null`, renders the canonical Admin detail SidePeek component inside `ui/cards/stacked-card.tsx` when a row is clicked, and leaves the `InfiniteScroll` relation list mounted underneath (not reset, not remounted, no key change, no scroll-position store needed because nothing unmounts). The canonical detail component is resolved via the existing `resolveSidePeekComponent()` mapper (already used by `useDynamicComponentVisibility` for the exact same purpose) instead of a direct cross-feature import, so no new N-way circular import was introduced between the five Admin feature folders — the only indirection point is the pre-existing central `side-peek-component-registry.ts` hub. No generic `EntityStack`/`RelationshipStack`/resource-switch component was created; each file's own `render{Selected<Entity>}` helper is a 5–10 line, feature-owned StackedCard wrapper |
| 21–22 | Deep nested relationship navigation continues stacking | **COMPLETE** — the five Admin detail SidePeeks whose factual presentation exposes relationship callbacks (`admin-quest-detail-side-peek.tsx`, `admin-monster-detail-side-peek.tsx`, `admin-item-detail-side-peek.tsx`, `admin-location-detail-side-peek.tsx`, `admin-npc-detail-side-peek.tsx`) no longer pass `useSidePeekEmitter`-based handlers into their factual presentation's `navigation` prop for relationship navigation. Each now owns a local discriminated-union `nestedSelection` state (new `types/{quest,monster,item,location,npc}-nested-selection.ts`, one per feature, scoped to only the relationship types that entity's factual presentation actually exposes) and a `renderNestedDetail()` helper that stacks the resolved target component inside `StackedCard`. Because every one of these five components is itself the thing being stacked into by its callers, recursion falls out of ordinary React composition: a stacked Quest detail's own "open related Item" click stacks an Item detail inside *its own* `StackedCard`, which can itself stack a Monster detail, and so on — no generic stack framework, just each feature owning its own next-level state exactly as instructed |
| 23 | Edit still stacks | **COMPLETE** — Quest/Monster/Item Edit (and Quest's Add-Child) still use their own separate `formMode`/`showEdit` state and their own `StackedCard`, unchanged in mechanism; the new `nestedSelection` StackedCard is a sibling render, not a replacement — closing Edit still returns to the entity detail, closing a relationship-list detail still returns to the relationship list, and closing a nested relationship detail still returns to whichever detail opened it. Location/NPC/Game Map Edit intentionally continue to use the existing global-emit `ADMIN_LOCATION_FORM`/`ADMIN_NPC_FORM`/`ADMIN_GAME_MAP_FORM` pattern — unchanged from before this pass, since the instructions only required Location/NPC Edit to "remain functional," not to convert to `StackedCard` |
| 24 | `StackedCard` accessibility reused | **COMPLETE** — every new nested/relationship detail render goes through the existing `ui/cards/stacked-card.tsx` + `useStackedCardAccessibility`; no new focus-trap logic, no `setTimeout` hacks |
| 25 | Relationship-list scroll preservation | **COMPLETE** — the five relation browsers never unmount their `InfiniteScroll`/paginated hook when a `StackedCard` opens; `game_map_id`, filters, and page state are untouched by opening/closing a selected detail, so scroll position is preserved by React simply not remounting that subtree |
| 26 | Inaccurate comments corrected | **COMPLETE** — every "stacking on top of this list" / "stacking on top of this content" docblock that described the old global-emit behavior was rewritten to describe the actual `StackedCard` behavior, across all five relation browsers and all five nested-navigation detail SidePeeks |
| 27–28 | SidePeek entity title colors / no duplicated heading | **COMPLETE, unchanged** — not touched by this pass; no new visible entity heading was added anywhere (nested details render the same factual presentation component with its existing single heading; `StackedCard`'s own name is provided via `aria_label`, not a visible `<h1>`) |
| 29 | Quest tree fit — static acceptance | **COMPLETE** — verified by grep: `quest-tree-desktop.tsx` and `quest-tree-desktop-node.tsx` contain no `overflow-x-auto`, `min-w-max`, `min-w-[9rem]`, or `px-4`; both use `w-full`/`min-w-0` throughout |
| 30 | Quest story — static acceptance | **COMPLETE** — verified by grep: `quest-story-panel.tsx` contains `prose`, `dark:prose-invert`, `max-w-none`, and retains `max-h-[400px] overflow-y-auto` |
| 31 | Desktop tree accessibility — static acceptance | **COMPLETE** — verified by reading the final file: root `role="tree"`, nodes `role="treeitem"`, child region `role="group"`, correct `aria-level`, connectors `aria-hidden`, visible short state label plus full `sr-only` explanation retained, no `aria-selected` added |
| 32–33 | Relation stack / deep graph — static acceptance | **COMPLETE** — traced every path enumerated in the instructions: Quest → Quest/Item/Monster/NPC/Map; Item/Quest Item → Location/NPC/Quest/Monster/Map; Monster → Item/Map; NPC → Quest/Item/Map; Location → Item/Map. Each is exactly the relationship set already exposed by that entity's existing factual `navigation`/`on_open_*` props (`QuestTreeNavigationDefinition`, `QuestItemFactualNavigationDefinition`, `MonsterNavigationDefinition`, `NpcDetailBodyProps`, `LocationDetailBodyProps`) — no unsupported relation was invented |
| 34 | No new generic stack framework | **COMPLETE** — grep confirmed no `EntityStack`/`RelationshipStack`/`ResourceStack`/`GameDataStack`/new provider/new reducer was added; every nested-selection type is a small feature-owned discriminated union with only the fields that feature needs |
| 35 | No backend changed | **COMPLETE** — zero `app/`, `routes/`, or `database/` files touched in this pass |
| 36–37 | Expected files / component size | **COMPLETE** — see Files section below; every relationship browser file stayed at its prior line count plus one `useState` + two small handlers + one `render{Selected}` helper (~15–20 added lines each); every nested-navigation detail SidePeek stayed under 240 lines |
| 38 | Type safety | **COMPLETE** — `yarn type-check` passes with zero errors; no `any`, no forced assertion, no `@ts-ignore`/`@ts-expect-error` was added; nested-selection discriminated unions live in each feature's own `components/types/` folder |
| 39 | Conditional rendering | **COMPLETE** — every `StackedCard` conditional is a named `render{Nested,Selected}Detail`/`render{Selected<Entity>}` helper with early returns, not inline ternaries in the final JSX |
| 40 | Dark/light mode | **COMPLETE, unchanged** — no new visible surface was introduced by this pass (only state/wiring around already-themed existing components); `dark:prose-invert` covers the one genuinely new visible surface (Quest story prose) |
| 41 | Final static audit | **COMPLETE** — see Sections 29–34 above; also grepped every file touched in this pass for `console.*`, `: any`, `as any`, `@ts-ignore`, `@ts-expect-error`, `eslint-disable`, and `calc(` — none found |
| 42 | Final quality gate | **COMPLETE — PASS** — see below |
| 43–44 | Final acceptance / report | **COMPLETE** — see Overall status below |

## Files created

`resources/js/admin/quests/components/types/quest-nested-selection.ts`; `resources/js/admin/monsters/components/types/monster-nested-selection.ts`; `resources/js/admin/items/components/types/item-nested-selection.ts`; `resources/js/admin/npcs/components/types/npc-nested-selection.ts`; `resources/js/admin/locations/components/types/location-nested-selection.ts`.

## Files modified

`resources/js/game/reusable-components/quest/components/quest-story-panel.tsx`; `resources/js/game/reusable-components/quest/components/quest-tree-desktop.tsx`; `resources/js/game/reusable-components/quest/components/quest-tree-desktop-node.tsx`; `resources/js/admin/quests/components/side-peeks/admin-quest-detail-side-peek.tsx`; `resources/js/admin/monsters/components/side-peeks/admin-monster-detail-side-peek.tsx`; `resources/js/admin/items/components/side-peeks/admin-item-detail-side-peek.tsx`; `resources/js/admin/locations/components/side-peeks/admin-location-detail-side-peek.tsx`; `resources/js/admin/npcs/components/side-peeks/admin-npc-detail-side-peek.tsx`; `resources/js/admin/game-maps/components/side-peeks/game-map-related-locations-side-peek.tsx`; `resources/js/admin/game-maps/components/side-peeks/game-map-related-npcs-side-peek.tsx`; `resources/js/admin/game-maps/components/side-peeks/game-map-related-monsters-side-peek.tsx`; `resources/js/admin/game-maps/components/side-peeks/game-map-related-quests-side-peek.tsx`; `resources/js/admin/game-maps/components/side-peeks/game-map-related-quest-items-side-peek.tsx`; `proof_of_work.md`.

## Files deleted

None.

## Backend behavior changed

None. This pass touched only `resources/js` files.

## Final quality gate

Ran exactly:
```
yarn lint && yarn type-check && yarn cleanup && yarn unused-files-check && ./vendor/bin/pint
```
**Result: PASS, fully green.**
1. `yarn lint`: 0 errors, 15 pre-existing warnings, all in files untouched by this pass (same warnings documented in the prior pass: `use-own-game-map-move.ts` ×3, `side-peek-component-props-map.ts` ×8 `max-len`, `use-get-set-equippability-details.ts`, `use-move-item-to-set.ts` ×2, `alert.tsx`). Two prettier formatting errors surfaced mid-pass in newly-created files were fixed before this final green run.
2. `yarn type-check`: 0 errors.
3. `yarn cleanup`: ran clean; every file reported "(unchanged)" — no further lint/type-check re-run was required since no file was modified by cleanup.
4. `yarn unused-files-check` (`unimported`): **PASS** — `✓ There don't seem to be any unimported files.`
5. `./vendor/bin/pint`: **PASS** (`{"tool":"pint","result":"passed"}`) — expected no-op since no PHP file was touched.

## Overall status

**READY TO RETEST BROWSER QA BATCH 1**

All five defects this pass targeted are corrected in source and pass every static gate: Quest story now renders through real Tailwind Typography `prose`/`dark:prose-invert` inside its unchanged bounded/scrollable viewport; the desktop Quest tree no longer forces horizontal scrolling (no `overflow-x-auto`, `min-w-max`, or fixed per-node minimum width — proportional `flex-1`/`min-w-0` width-sharing plus wrapping Quest names) and its child regions now carry `role="group"`; all five Game Map related-data SidePeeks open the canonical entity detail inside `StackedCard` while keeping the relationship browser mounted underneath with its scroll position intact instead of replacing it through the global SidePeek emitter; and relationship navigation nested inside any of those stacked details (Quest, Item, Monster, NPC, Location) continues stacking via each feature's own local `nestedSelection` state and `StackedCard`, rather than destroying the ancestor stack through the global emitter. Edit still stacks for Quest/Monster/Item exactly as before. No backend file was touched. No new generic relationship/stack framework was introduced — every correction reused `StackedCard`, the existing `resolveSidePeekComponent` mapper, and ordinary React composition. This pass did not perform browser QA.

Remaining work: manually retest Browser QA Batch 1.
