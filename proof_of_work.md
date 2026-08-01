# Proof of work

## Incident and root cause

- The isolated `GuideQuestControllerApiTest::test_next_guide_quest_has_one_of_the_requirements_when_completing_the_previous_quest` reached approximately 11.4 GB RSS and was killed by signal 9.
- The confirmed path was Guide Quest hand-in, synchronous battle-reward processing, container resolution failure, `failed()` recovery, and immediate synchronous redispatch repeating until OOM.
- `CharacterRewardService` imported the nonexistent `App\Flare\Builders\BuildCosmicItem`, `BuildMythicItem`, and `BuildUniqueItem`. These now use `App\Flare\Items\Builders\BuildCosmicItem`, `BuildMythicItem`, and `BuildUniqueItem`, matching the concrete instances supplied by `app/Flare/Providers/ServiceProvider.php`. Constructor order and reward behavior were not changed.

## Confirmed namespace corrections

- Builder imports were corrected for `BuildCosmicItem`, `BuildMythicItem`, `BuildUniqueItem`, and `RandomItemDropBuilder`.
- `AlchemyItemType`, `ArmourType`, and `ItemType` imports across the application and tests were corrected from `App\Game\Character\CharacterInventory\Values` to `App\Flare\Items\Values`; existing aliases and distinct enum usages were preserved.
- `LocationEffectValue` now imports from `App\Flare\Values`.
- `CharacterResistanceInfoTransformer` and `CharacterStatDetailsTransformer` now import from `App\Game\Character\CharacterSheet\Transformers`.
- `IsCharacterExploring` now imports from `App\Game\Automation\Middleware`.
- `BuildMonsterCacheService` now imports from `App\Game\Monsters\Services`.
- Container resolution exposed implicit stale same-namespace types in `CharacterInventoryService`; explicit imports were added for `App\Flare\Items\Enricher\ItemEnricherFactory`, `App\Flare\Items\Transformers\EquippableItemTransformer`, and `App\Flare\Items\Transformers\QuestItemTransformer`, matching its provider arguments.

## Individually inspected missing imports

- `App\Flare\Models\Notification` and `App\Game\Core\Events\UpdateNotificationsBroadcastEvent`: the notifications table and implementation were removed. The dead notification creation/broadcast integration was removed from `UnitReturnService`; the active kingdom log and server-message behavior remains.
- `App\Flare\Models\SubmittedSurvey` and `App\Game\Survey\Events\ShowSurvey`: the survey model/event implementation is absent and the snapshot service is an empty retired stub. The dead model truncation and per-character survey broadcast integration was removed while the feedback event ending flow remains.
- `App\Game\Messages\Types\MessageType`: replaced with the current, case-compatible `CurrenciesMessageTypes` enum for `GOLD_RUSH` and `GOLD_CAPPED`.
- `App\Game\Core\Listeners\UpdateTopBarListener`: no implementation exists. Its stale event-provider entry was removed; the current `CharacterTopsUpdateListener` remains registered for `UpdateTopBarEvent`.
- `App\Flare\Transformers\ItemComparisonTransfromer`: replaced with the current `EquippableItemTransformer`, passing the `SetSlot` shape its transformer declares.
- `App\Flare\Models\QuestItemSlot`: the obsolete, unused `CreateQuestItemSlot` test trait was removed; inventory-slot tests use the current `InventorySlot` trait/model.
- `App\Http\Middleware\GameAuthentication`: the middleware no longer exists. The obsolete test-only import and `withoutMiddleware` call were removed; registration coverage otherwise remains unchanged.
- `App\Flare\Models\Notification` in the unused `CreateNotification` test trait: the obsolete trait was removed.

## Failed-hook recursion safety

- `ProcessCharacterBattleRewardQueue::failed()` still logs the original failure, performs ledger-backed and orphan recovery, checks processing rows, and checks pending/resumable rows.
- Immediately before continuation dispatch it now reads `queue.connections.battle_reward_processing.driver`. For `sync`, it logs on `reward_processing` that continuation was skipped and returns without completing or dispatching the recovered request.
- For every non-sync driver, the existing connection, queue, and continuation dispatch are unchanged, preserving production Redis behavior.
- Existing failed-hook continuation tests explicitly configure the named connection as non-sync. A focused sync-driver test verifies that a pending request stays pending and no continuation is pushed.

## Verification

- The static PSR-4 import audit inspected every `use App\...;` import in `app/**` and `tests/**`, handled aliases, and reported no missing class paths after review.
- All seven targeted stale-namespace `rg` searches returned no matches.
- Targeted PHP syntax checks passed for all five requested files.
- `TIME_ZONE=America/Edmonton php artisan package:discover --ansi` passed.
- An application bootstrap through the console kernel resolved `CharacterRewardService`, `BattleRewardService`, and `GuideQuestService` without dispatching jobs or creating records.
- The complete PHP syntax loop over `app`, `bootstrap`, `config`, `database`, `routes`, and `tests` passed.
- `yarn lint && yarn type-check && yarn cleanup && yarn unused-files-check && ./vendor/bin/pint` completed successfully.
- PHPUnit and coverage were not run locally.
- Migrations were not run locally.
- No prohibited Git command was run. No commit or push was performed.
- The isolated Guide Quest test was not rerun, so no passing-test claim is made.
