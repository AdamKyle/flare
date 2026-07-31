# Proof of Work

## Scope and instructions

- Read every `.agents/skills/**/SKILL.md`, including nested skill files.
- Inspected the relevant Batch Crafting, Trinketry, kingdom ownership middleware, kingdom controller regression tests, admin dashboard, and bounded log reader paths before validation.
- Treated the supplied PHPUnit result as the initial failure report: 2 errors and 8 failures across 4,832 tests and 12,433 assertions.
- Did not run coverage or frontend commands.
- Did not run the full PHPUnit suite because the user's final instruction explicitly said: `DO NOT RUN THE FULL TEST SUITE ONLY FIX THE ISSUES AT HAND AND RUN THOSE TESTS.`

## Confirmed root causes and corrections

### Trinketry controller fixtures

The three controller fixtures were stale. Production correctly routes a start through `BatchCraftingService::start()`, start blockers, `BatchCraftingProcessor::trinketryCostPreview()`, and `TrinketCraftingService::craftingCost()`.

Corrected independently in:

- `tests/Feature/Game/BatchCrafting/Controllers/BatchCraftingControllerTest.php`
  - `testStartTrinketryBatch`
  - `testTrinketryAllowsKeepBestDestroyRestDisposition`
  - `testTrinketryUsesCraftedItemsSetCapacityNotNormalInventory`

Each fixture now has:

- `inventory_max` of 10;
- 100 Gold Dust;
- 100 Copper Coins;
- a level 1, unlocked `Trinketry` crafting skill with max level 400;
- one craftable, XP-eligible Trinketry trinket costing 1 Gold Dust and 1 Copper Coin.

Shards remain incidental and are not the required Trinketry currency. The capacity test preserves the full one-slot Crafted Items Set and asserts the 422 validation shape before checking `errors.batch_crafting.0` for `Crafted Items Set is full`.

No Trinketry production behavior was changed by this task. `BatchCraftingService`, `BatchCraftingProcessor`, and `TrinketCraftingService` were not edited by this task.

### Kingdom ownership JSON contract

The broad structured JSON response in `DoesKingdomBelongToAuthorizedUser` had regressed legacy clients on non-building kingdom routes.

Corrected in:

- `app/Game/Kingdoms/Middleware/DoesKingdomBelongToAuthorizedUser.php`
- `tests/Feature/Game/Kingdoms/Middleware/DoesKingdomBelongToAuthorizedUserTest.php`

The middleware now preserves the ownership decision, redirects, and building rejection logging while branching JSON responses by the exact `building` route parameter:

- `building` is present: HTTP 422 with `message = You do not own this kingdom building.` and `reason = ownership_mismatch`.
- `building` is absent: HTTP 422 with only `error = Nope. Not allowed to do that.`

The middleware regression tests assert the exact legacy error and absence of `message` and `reason`. Routes using `kingdomBuilding`, including resource expansion routes, retain the legacy response. The existing `KingdomBuildingsControllerTest` confirms the building-specific contract and logging path remain intact.

No kingdom controller, route, or ownership rule was changed by this task.

### Admin log memory measurement

The old assertion used the PHPUnit process-wide historical peak, which included bootstrap, fixture generation, prior tests, and retained allocations.

Corrected in:

- `tests/Feature/Admin/AdminLogsDashboardTest.php`

After both unchanged large fixtures are written, the test now:

1. collects garbage cycles;
2. resets peak memory usage;
3. records baseline allocated memory;
4. calls the real dashboard service;
5. calculates request-local peak growth;
6. asserts growth is less than 32 MiB.

The functional assertions remain: at most 50 entries, `Newest file entry` is returned, and `next_cursor` is non-null.

`AdminLogsDashboardService` and `LogReader` were not edited by this task. Inspection confirmed the existing shared 2 MiB request byte budget, 64 KiB backward chunks, and 50-entry page limit remain.

## Targeted validation

Initial failures were the supplied 2 errors and 8 failures from the three factual causes above.

Commands and results:

- `php -d memory_limit=-1 -d max_execution_time=0 vendor/bin/phpunit tests/Feature/Game/BatchCrafting/Controllers/BatchCraftingControllerTest.php`
  - PASS: 89 tests, 133 assertions.
- `php -d memory_limit=-1 -d max_execution_time=0 vendor/bin/phpunit tests/Feature/Game/Kingdoms/Middleware/DoesKingdomBelongToAuthorizedUserTest.php`
  - PASS: 3 tests, 9 assertions.
- `php -d memory_limit=-1 -d max_execution_time=0 vendor/bin/phpunit tests/Feature/Game/Kingdoms/Controllers/Api/KingdomInformationTest.php`
  - PASS: 4 tests, 8 assertions.
- `php -d memory_limit=-1 -d max_execution_time=0 vendor/bin/phpunit tests/Feature/Game/Kingdoms/Controllers/Api/KingdomSteelControllerTest.php`
  - PASS: 4 tests, 14 assertions.
- `php -d memory_limit=-1 -d max_execution_time=0 vendor/bin/phpunit tests/Feature/Game/Kingdoms/Controllers/Api/KingdomUnitsControllerTest.php`
  - PASS: 5 tests, 22 assertions.
- `php -d memory_limit=-1 -d max_execution_time=0 vendor/bin/phpunit tests/Feature/Game/Kingdoms/Controllers/Api/ResourceBuildingExpansionControllerTest.php`
  - The first process completed between polling calls and the runner discarded its final output. It was run once more solely to obtain a factual result.
  - PASS on the output-recovery run: 4 tests, 22 assertions. No correction was required between runs.
- `php -d memory_limit=-1 -d max_execution_time=0 vendor/bin/phpunit tests/Feature/Game/Kingdoms/Controllers/Api/KingdomBuildingsControllerTest.php`
  - PASS: 22 tests, 97 assertions.
- `php -d memory_limit=-1 -d max_execution_time=0 vendor/bin/phpunit tests/Feature/Admin/AdminLogsDashboardTest.php`
  - PASS: 46 tests, 128 assertions.

All targeted commands have zero errors and zero failures.

## Final source audit

- Valid Trinketry tests use a real skill, XP-eligible item, Gold Dust, and Copper Coins.
- Shards were not restored as a Trinketry currency.
- The full Crafted Items Set capacity blocker is reached through the real start endpoint.
- Non-building ownership failures return only the legacy `error`.
- Exact `building` ownership failures retain `message` and `reason`.
- Building ownership rejection logging and identifiers remain.
- Resource expansion retains the legacy response.
- Admin memory is measured as request-local peak growth under 32 MiB.
- Admin fixture sizes, shared 2 MiB budget, and 50-entry limit remain unchanged.
- This task changed no migration, frontend file, package, lockfile, controller, route, schema, factory, or unrelated assertion.
- No correct assertion was weakened.

## Files changed by this task

- `app/Game/Kingdoms/Middleware/DoesKingdomBelongToAuthorizedUser.php`
- `tests/Feature/Game/BatchCrafting/Controllers/BatchCraftingControllerTest.php`
- `tests/Feature/Admin/AdminLogsDashboardTest.php`
- `tests/Feature/Game/Kingdoms/Middleware/DoesKingdomBelongToAuthorizedUserTest.php`
- `proof_of_work.md`

The workspace contained many unrelated pre-existing changes visible in `git status --short`; they were preserved and not edited as part of this task.
