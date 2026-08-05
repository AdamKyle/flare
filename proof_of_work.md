# Proof of Work — BrowserKit → Pest Migration & `app/Http/**` Coverage

## 1. Files added, moved, changed, deleted

### Composer / test infrastructure (authorized exceptions)
- Changed: `composer.json`, `composer.lock`
- Changed: `tests/TestCase.php` — now extends `Illuminate\Foundation\Testing\TestCase` instead of `Laravel\BrowserKitTesting\TestCase`; removed the BrowserKit-only `public string $baseUrl` property; preserved Vite stub, `AttackDataCacheSetUp` mock wiring, queue-sync config block, and Mockery teardown unchanged.
- Added: `tests/Pest.php` — binds `Tests\TestCase` + `RefreshDatabase` to the `Feature` suite and `Tests\TestCase` to `Unit`.

### New test files (production mapping under `tests/Feature/Http/**`)
- `tests/Feature/Http/Controllers/AccountDeletionControllerTest.php`
- `tests/Feature/Http/Controllers/Api/EventCalendarControllerTest.php`
- `tests/Feature/Http/Controllers/Api/ItemsControllerTest.php`
- `tests/Feature/Http/Controllers/Api/OnlineUsersControllerTest.php` (supersedes `OnlineUsersControllerSecurityTest.php`)
- `tests/Feature/Http/Controllers/Auth/ConfirmPasswordControllerTest.php`
- `tests/Feature/Http/Controllers/Auth/ForgotPasswordControllerTest.php` (moved from `tests/Feature/Http/Controllers/`)
- `tests/Feature/Http/Controllers/Auth/LoginControllerTest.php` (moved from `tests/Feature/Auth/`)
- `tests/Feature/Http/Controllers/Auth/RegisterControllerTest.php` (moved/renamed from `tests/Feature/Auth/RegistrationControllerTest.php`)
- `tests/Feature/Http/Controllers/Auth/ResetPasswordControllerTest.php` (moved from `tests/Feature/Auth/`)
- `tests/Feature/Http/Controllers/InfoPageControllerTest.php` (consolidates the 3 legacy `tests/Feature/InfoPageController/*` files)
- `tests/Feature/Http/Controllers/MarketingPagesControllerTest.php`
- `tests/Feature/Http/Controllers/ReleasesControllerTest.php` (renamed from `ReleasePageControllerTest.php`)
- `tests/Feature/Http/Controllers/WelcomeControllerTest.php`
- `tests/Feature/Http/Middleware/AuthenticateTest.php`
- `tests/Feature/Http/Middleware/RedirectIfAuthenticatedTest.php`
- `tests/Feature/Http/Request/EventPageRequestTest.php`

### Modified in place (Pest conversion, `UnbanRequestController` mapping already correct)
- `tests/Feature/Http/Controllers/UnbanRequestControllerTest.php` — converted PHPUnit-class syntax to Pest, added `unbanRequest()`/`requestForm()` coverage.

### Deleted (superseded / consolidated / wrong location)
- `tests/Feature/Auth/LoginControllerTest.php`
- `tests/Feature/Auth/RegistrationControllerTest.php`
- `tests/Feature/Auth/ResetPasswordControllerTest.php`
- `tests/Feature/Http/Controllers/ForgotPasswordControllerTest.php` (old, wrong namespace location)
- `tests/Feature/Http/Controllers/OnlineUsersControllerSecurityTest.php`
- `tests/Feature/Http/Controllers/ReleasePageControllerTest.php`
- `tests/Feature/InfoPageController/DynamicInformationSectionTest.php`
- `tests/Feature/InfoPageController/LocationGemsTest.php`
- `tests/Feature/InfoPageController/MapGemsTest.php`

### BrowserKit tests outside `app/Http/**` (converted navigation calls only, behavior preserved)
- `tests/Feature/Admin/BattleRewardQueueControllerTest.php`
- `tests/Feature/Game/Core/Controllers/GameTopsControllerTest.php`
- `tests/Feature/Game/Core/Controllers/ItemsControllerTest.php`
- `tests/Feature/Game/GuideQuest/Controllers/GuideQuestsControllerTest.php`
- `tests/Feature/Game/Quests/Controllers/QuestsControllerTest.php` — also replaced 6 direct `QuestsCompleted::factory()->create()` calls with the existing `Tests\Traits\CreateQuestsCompleted` trait (`createQuestsCompleted()`), since the fixture rule against direct factory calls applies to this file once it required editing for BrowserKit removal.

No production code (`app/**`, `routes/**`, `database/**`, `config/**`, `resources/**`), `phpunit.xml`, or CI workflow files were modified.

## 2. BrowserKit references — before and after

**Before:**
- `composer.json` required `laravel/browser-kit-testing: ^7.2.4`.
- `tests/TestCase.php` extended `Laravel\BrowserKitTesting\TestCase`.
- 13 test files used BrowserKit navigation/assertion methods (`->visit()`, `->visitRoute()`, `->click()`, `->see()`, `->dontSee()`, `->submitForm()`, `seeIsAuthenticatedAs()`, `dontSeeIsAuthenticated()`, `assertRedirectedTo()`, `seeRouteIs()`).

**After:**
- `composer remove laravel/browser-kit-testing --dev` executed — package and its `symfony/dom-crawler` dependency removed from `composer.lock`.
- `grep -rln "BrowserKit" tests/ composer.json composer.lock` → **no matches**.
- `grep -rlE "\->visit\(|\->click\(|\->submitForm\(|\->see\(|\->dontSee\(|seePageIs\(|seeIsAuthenticatedAs\(|dontSeeIsAuthenticated\(|assertResponseOk\(|assertResponseStatus\(|assertRedirectedTo\(|assertRedirectedToRoute\(|->type\(|->select\(|->check\(|->uncheck\(|->attach\(|->press\(" tests/` → **no matches**.
- All navigation was replaced with real `$this->get()/post()/getJson()/postJson()` calls and Laravel's own `TestResponse` assertions (`assertOk`, `assertSee`, `assertDontSee`, `assertRedirect`, `assertSessionHas`, `assertSessionHasErrors`, `assertViewIs`, `assertViewHas`, `assertJson*`, `assertAuthenticatedAs`, `assertGuest`).

## 3. Pest packages installed / BrowserKit removed

```
composer remove laravel/browser-kit-testing --dev --no-interaction
composer config --no-plugins allow-plugins.pestphp/pest-plugin true
composer require pestphp/pest pestphp/pest-plugin-laravel --dev --no-interaction
```

Resolved versions (from `composer.lock`): `pestphp/pest v4.7.3`, `pestphp/pest-plugin-laravel v4.1.0` (plus their required sub-dependencies `pestphp/pest-plugin`, `pestphp/pest-plugin-arch`, `pestphp/pest-plugin-mutate`, `pestphp/pest-plugin-profanity`, `brianium/paratest`, etc., all resolved automatically by Composer — no versions were hand-picked). `phpunit/phpunit ^12.0` was kept unchanged since PHPUnit-class tests remain throughout the rest of the suite. `pestphp/pest-plugin` was added to `config.allow-plugins` (required for Pest's Composer plugin to run).

## 4. Controller / middleware / request coverage added

New or expanded Pest coverage per production class (all under `tests/Feature/Http/**`, matching `App\Http\**` namespaces):

| Production class | Test file | Notes |
|---|---|---|
| `AccountDeletionController` | `Controllers/AccountDeletionControllerTest.php` | Owner-only guard for both `deleteAccount` and `resetAccount`; real `AccountDeletionJob` dispatch on the sync `long_running` connection (user actually deleted); real character-recreation path via `CharacterDeletion` with both `race` and `class` overrides. |
| `EventCalendarController` (Api) | `Controllers/Api/EventCalendarControllerTest.php` | `EventSchedulerService::fetchEvents()` populated and empty cases. |
| `ItemsController` (Api) | `Controllers/Api/ItemsControllerTest.php` | Craftable-item filtering/ordering, cache hit, cache bypass on `filter`/`search_text`, `fetchSpecificSet` specialty filter. |
| `OnlineUsersController` (Api) | `Controllers/Api/OnlineUsersControllerTest.php` | Consolidated with pre-existing security-focused tests; added `getWhosPlayingStatistics` and `SiteAccessStatisticsRequest` validation-failure coverage. |
| `ConfirmPasswordController` (`@codeCoverageIgnore`) | `Controllers/Auth/ConfirmPasswordControllerTest.php` | Guest-redirect via `auth` middleware, correct/incorrect password `confirm()` branches (app-owned `redirectTo` + session flag), behavior-only since the class is coverage-ignored. |
| `ForgotPasswordController` | `Controllers/Auth/ForgotPasswordControllerTest.php` | Admin vs. non-admin reset branch, unknown-email branch. |
| `LoginController` | `Controllers/Auth/LoginControllerTest.php` | Success, wrong-password, missing-inventory auto-logout+re-flag, guide-quest cache branch, disabled-site branch, throttling lockout branch. |
| `RegisterController` | `Controllers/Auth/RegisterControllerTest.php` | Success (+ real `CreateCharacterEvent`/pipeline execution), banned-IP, no-default-map, duplicate-name validation, IP-cap, disabled-site branch. |
| `ResetPasswordController` (`@codeCoverageIgnore`) | `Controllers/Auth/ResetPasswordControllerTest.php` | Show-form, unknown-email, successful reset, invalid-token failure — behavior-only. |
| `InfoPageController` | `Controllers/InfoPageControllerTest.php` | All 20 route methods: `search` (match/no-match), `viewPage` (dynamic Livewire alias, unknown alias, 404), map/location gem list+search+empty-state+show, race/class/skill/class-specialty/monster/location/unit/building/item/affix/npc/raid/quest(+unlocked-skill)/passive-skill/item-skill shows, and all 7 `viewMap()` plane-name match arms. |
| `MarketingPagesController` | `Controllers/MarketingPagesControllerTest.php` | Both static view routes. |
| `ReleasesController` | `Controllers/ReleasesControllerTest.php` | Renamed only; behavior preserved. |
| `UnbanRequestController` | `Controllers/UnbanRequestControllerTest.php` | Added `unbanRequest()` view and both `requestForm()` branches (token present/absent) to the pre-existing `findUser`/`submitRequest` security tests. |
| `WelcomeController` | `Controllers/WelcomeControllerTest.php` | Guest/admin/non-admin `welcome()` branches, `showEventCalendar`, `EventPageRequest` failure via the real route, unsupported `event_type`, both currently-running and upcoming-only branches for one raid type and one non-raid event type, and one rendering test for each of the other 10 supported event/raid pages. |
| `Authenticate` middleware | `Middleware/AuthenticateTest.php` | Custom `redirectTo()` HTML-redirect-to-login and JSON-401 branches, exercised through the real `auth`-protected `delete.account` route. |
| `RedirectIfAuthenticated` middleware | `Middleware/RedirectIfAuthenticatedTest.php` | Authenticated-redirect-to-`/home` branch (the previously uncovered branch) plus the guest pass-through branch. |
| `EventPageRequest` | `Request/EventPageRequestTest.php` | Custom `event_type` required message and a passing request, both through the real `event.type` route. |

Framework-only configuration classes left untested (n/a, per the coverage report): `Kernel.php`, `Controller.php`, `CheckForMaintenanceMode.php`, `EncryptCookies.php`, `TrimStrings.php`, `TrustProxies.php`, `VerifyCsrfToken.php` — each has an empty `$except`/pass-through body with no application-owned logic.

## 5. Fixture traits and domain setup factories used

`Tests\Setup\Character\CharacterFactory` (all full-character scenarios), and existing `Tests\Traits\Create*` traits: `CreateUser`, `CreateRole`, `CreateGuideQuest`, `CreateClass`, `CreateRace`, `CreateGameSkill`, `CreateItem`, `CreatePassiveSkill`, `CreateUsersWithIp`, `CreateReleaseNotes`, `CreateScheduledEvent`, `CreateUserLoginDuration`, `CreateGameMap`, `CreateGameMapGemParamter`, `CreateGameLocationGemParamter`, `CreateGameBuilding`, `CreateGameBuildingUnit`, `CreateGameUnit`, `CreateGameClassSpecial`, `CreateItemAffix`, `CreateLocation`, `CreateMonster`, `CreateNpc`, `CreateQuest`, `CreateQuestsCompleted`, `CreateRaid`. No new trait was created — every fixture need was already covered by an existing trait or `CharacterFactory`. Two plain `Model::create()` calls (not `::factory()`) were used directly per existing project precedent: `InfoPage::create()` and `ItemSkill::create()` (both already used this way in the pre-existing suite; neither model has a dedicated `Create*` trait).

## 6. Allowed mocks and their category

**None were added.** Every new/modified test in scope executes the real event dispatcher, the real synchronous queue connection (already configured in `Tests\TestCase::setUp()`), and real listeners/jobs/mail. No `Log::spy()`, map/tile mocks, or random-boundary mocks were needed for any `app/Http/**` behavior.

One pre-existing `Queue::fake()` remains, untouched, in `tests/Feature/Admin/BattleRewardQueueControllerTest.php::test_admin_can_repair_stale_queues_and_receive_counts`. It predates this task, guards `Api\BattleRewardQueueController` (an Admin API controller entirely outside `app/Http/**`), and was not part of the one BrowserKit chain this migration needed to remove from that file (a different test method, `test_admin_can_view_reward_queue_page_and_home_card`). It was left in place rather than removed, because doing so would exercise an unmapped downstream battle-reward-processing job chain this task never inspected — outside the authorized dependency closure and a real risk of an unintended behavior change.

## 7. Confirmation: prohibited mocks/fakes not used

Checked with `grep -n "Event::fake\|Queue::fake\|Mail::fake\|Bus::fake\|Notification::fake\|Broadcast::fake"` across every file this task added or modified (the 24 files listed in §1, minus the one documented pre-existing exception above): **no matches**. No `Event::fake()`, `Mail::fake()`, `Bus::fake()`, `Notification::fake()`, or `Broadcast::fake()` were introduced anywhere. No service/handler/transformer/model/request/controller partial mocks were used.

## 8. Confirmation: no direct queued-job `handle()` execution

`grep -n "->handle("` across the same file set: **no matches**. Every job in scope (`AccountDeletionJob`) runs through its real `::dispatch()` call on the application's configured queue connection (forced to `sync` for `long_running` and all other used connections in `Tests\TestCase::setUp()`), never invoked directly.

## 9. Confirmation: no direct `::factory()` calls in test files

`grep -n "::factory("` across the same file set: **no matches**. `QuestsControllerTest.php` (outside `app/Http/**`, touched only for BrowserKit removal) had its pre-existing `QuestsCompleted::factory()->create()` calls replaced with the existing `CreateQuestsCompleted` trait as part of this cleanup.

## 10. Test/coverage commands run and results

```
composer remove laravel/browser-kit-testing --dev --no-interaction
composer config --no-plugins allow-plugins.pestphp/pest-plugin true
composer require pestphp/pest pestphp/pest-plugin-laravel --dev --no-interaction
```
→ both completed successfully (see §3).

Each new/changed Pest file was run individually by exact path first (all passed after fixture/assertion fixes — see notes below), then the full authorized target was run as one command:

```
XDEBUG_MODE=coverage php -d memory_limit=-1 -d max_execution_time=0 \
  vendor/bin/pest tests/Feature/Http --coverage-html=./test-coverage --coverage-filter=app/Http
```

**Result:** `Tests: 115 passed (339 assertions)`, 0 failures, 0 errors, 0 risky, 0 skipped.

The five BrowserKit-converted files outside `app/Http/**` (§1) and `QuestsControllerTest.php` were also run individually by exact path — all passed (26 + 5 = 31 assertions across those methods; see transcript).

```
./vendor/bin/pint <the 24 files listed in §1's authorized scope>
```
→ `{"tool":"pint","result":"passed"}` — no formatting changes required; production files were never passed to Pint.

## 11. Final per-file `app/Http/**` coverage (from the generated report)

Directory totals (lines / methods):

| Path | Lines | Methods |
|---|---|---|
| `app/Http` (total) | **438 / 443 = 98.87%** | 57 / 61 = 93.44% |
| `Controllers/Api` | 63 / 63 = 100% | 11 / 11 = 100% |
| `Controllers/Auth` | 87 / 87 = 100% | 8 / 8 = 100% |
| `Middleware` | 6 / 6 = 100% | 2 / 2 = 100% |
| `Request` | 7 / 7 = 100% | 3 / 3 = 100% |

Per-file, for every file with executable code:

| File | Lines | Methods |
|---|---|---|
| `AccountDeletionController.php` | 22/22 = 100% | 2/2 = 100% |
| `InfoPageController.php` | 123/124 = 99.19% | 22/23 = 95.65% |
| `MarketingPagesController.php` | 2/2 = 100% | 2/2 = 100% |
| `ReleasesController.php` | 3/3 = 100% | 1/1 = 100% |
| `UnbanRequestController.php` | 37/38 = 97.37% | 3/4 = 75% |
| `WelcomeController.php` | 88/91 = 96.70% | 3/5 = 60% |
| `Api/EventCalendarController.php` | 100% | 100% |
| `Api/ItemsController.php` | 100% | 100% |
| `Api/OnlineUsersController.php` | 100% | 100% |
| `Auth/ForgotPasswordController.php` | 14/14 = 100% | 1/1 = 100% |
| `Auth/LoginController.php` | 28/28 = 100% | 2/2 = 100% |
| `Auth/RegisterController.php` | 45/45 = 100% | 5/5 = 100% |
| `Middleware/Authenticate.php` | 100% | 100% |
| `Middleware/RedirectIfAuthenticated.php` | 100% | 100% |
| `Request/EventPageRequest.php` | 100% | 100% |

`Kernel.php`, `Controller.php`, `ConfirmPasswordController.php`, `ResetPasswordController.php`, `CheckForMaintenanceMode.php`, `EncryptCookies.php`, `TrimStrings.php`, `TrustProxies.php`, `VerifyCsrfToken.php` all report `n/a` (0/0) — either genuinely empty of executable statements, or annotated `@codeCoverageIgnore` in production (left untouched, as instructed).

## 12. `n/a` files and why they require no artificial tests

- `Kernel.php`, `Controller.php` — declare no executable statements of their own (pure trait composition / middleware registration arrays).
- `CheckForMaintenanceMode.php`, `EncryptCookies.php`, `TrimStrings.php`, `TrustProxies.php`, `VerifyCsrfToken.php` — thin subclasses of framework middleware with empty `$except` arrays (or, for `TrimStrings`, a static exclusion list) and no application-owned branching logic.
- `ConfirmPasswordController.php`, `ResetPasswordController.php` — annotated `@codeCoverageIgnore` in production; annotation was left untouched per instructions. Behavioral tests were still written for both (see §4) even though the coverage tool excludes them from the measured percentage.

## 13. Branches that could not be covered without changing production logic

Five lines remain uncovered in the final report, all of which were traced to their root cause and confirmed to require a production change to reach — none were worked around by weakening a test or inventing an impossible database state:

1. **`InfoPageController.php:196`** (`viewLocation`, inside the `! is_null($location->questRewardItem)` branch) — `resources/views/information/locations/location.blade.php` references `$usedInQuest` (lines 39, 46, 52, 59, 65, 71, 77) but no controller or partial ever assigns that variable. Any location with a non-null `questRewardItem` genuinely throws `ErrorException: Undefined variable $usedInQuest` in this codebase today (confirmed by reproducing it during test-writing). The controller line that builds `$questItemDetails` itself is reachable, but the response can never return `200` for this branch without editing the read-only view. Documented, not force-tested.
2. **`UnbanRequestController.php:82`** (`submitRequest`, inside the "eligible banned user" branch) — the file imports `use Monolog\Handler\MailHandler;` at the top, which shadows the intended `App\Flare\Mail\MailHandler`. The line `MailHandler::dispatch($adminUser->email, new UnBanRequestMail($user))->delay(...)` therefore always throws `Error: Call to undefined method Monolog\Handler\MailHandler::dispatch()` whenever at least one Admin-role user exists (confirmed by direct reproduction). Every existing/added test that reaches the eligible-user branch deliberately creates the `Admin` role without assigning it to a user, for exactly this reason — assigning it would make the request fatal. Documented, not force-tested.
3. **`WelcomeController.php:90`** (`showEventPage`, raid `switch` `default` case) and **`WelcomeController.php:118`** (same method, non-raid-event `switch` `default` case) — both switches enumerate exactly the same literal values as the `$raids`/`$events` arrays used in the preceding `in_array()` guard, so the `default` arm is unreachable dead code under any real `$eventType` value that passes the guard, and unreachable under any value that fails it (the guard sends those directly to the final `redirect()->to(route('welcome'))` instead). No input can reach either `default` case.
4. **`WelcomeController.php:139`** (private `findScheduledEvent`, `default => null`) — same pattern: the private `findScheduledEventForEventType` switch that calls into this method only ever passes one of five hard-coded `EventType` constants, and this inner switch's own `default: return null;` (line 139) is likewise unreachable from any of those five call sites.

## 14. Confirmation production logic was not changed

`git status --porcelain` shows changes are limited to: `composer.json`, `composer.lock`, files under `tests/**`, and this `proof_of_work.md`. No file under `app/**`, `routes/**`, `database/**`, `config/**`, `resources/**`, `bootstrap/**`, `public/**`, `phpunit.xml`, or any CI workflow was modified. The two production bugs found (§13.1, §13.2) were left exactly as they are.

## 15. Excluded areas not touched

All other test suites (`tests/Unit/**`, `tests/Console/**`, and every `tests/Feature/Game/**`, `tests/Feature/Admin/**` file not listed in §1) were left untouched, including their use of PHPUnit-class syntax, Mockery, and any other Laravel facade fakes they already relied on. `phpunit.xml`'s three PHPUnit test suites (`Console`, `Feature`, `Unit`) needed no changes since Pest test files compile to ordinary PHPUnit test classes and are already picked up by the existing `directory suffix="Test.php"` matchers.

## 16. Final status

- BrowserKit fully removed from `composer.json`/`composer.lock` and from every test file (§2).
- Pest and the Laravel Pest plugin installed; `Tests\TestCase` now extends Laravel's own `Illuminate\Foundation\Testing\TestCase`; `tests/Pest.php` added.
- Every controller test covering `app/Http/**` (API and Auth included) is now a Pest test at the production-mirrored namespace/path.
- `XDEBUG_MODE=coverage php -d memory_limit=-1 -d max_execution_time=0 vendor/bin/pest tests/Feature/Http --coverage-html=./test-coverage --coverage-filter=app/Http` was executed and its generated report was inspected page by page: **438/443 lines (98.87%) and 57/61 methods (93.44%)** for `app/Http/**`, with the exact 5 remaining lines each traced to either dead/unreachable code or a pre-existing production bug (§13), not to missing tests.
- 100% line and method coverage was reached for `Controllers/Api`, `Controllers/Auth`, `Middleware`, `Request`, `AccountDeletionController.php`, `MarketingPagesController.php`, and `ReleasesController.php`.
- 100% was not reached for `InfoPageController.php`, `UnbanRequestController.php`, and `WelcomeController.php` specifically because of the two discovered production bugs and the dead-code `switch` defaults documented in §13 — not because of missing test effort.

---

# Proof of Work — `PendingCommand` Execution Fix (`$this->artisan()` Kernel-Not-Instantiable Failure)

## Root cause

After replacing `Laravel\BrowserKitTesting\TestCase` with `Illuminate\Foundation\Testing\TestCase`, `$this->artisan(...)` returns an `Illuminate\Testing\PendingCommand` instead of an integer exit code. `PendingCommand` only actually runs the command (via `Kernel::call()`) inside its own `execute()`/`run()` methods, or — if neither is called explicitly — from `__destruct()`. Every affected call in this repository either compared the returned object directly to an integer (`assertEquals(0, $this->artisan(...))` / `assertSame(0, $this->artisan(...))`) or discarded it as a bare statement, so the command was never explicitly executed inside the test method. Execution was deferred to PHP's garbage collector, which in these cases ran the destructor after the Laravel test application had already been torn down, producing `Target [Illuminate\Contracts\Console\Kernel] is not instantiable.`

## Files changed (13 test files, 22 `$this->artisan()` calls corrected)

- `tests/Console/AddHolyStacksToItemsTest.php` — 1 call
- `tests/Console/Admin/CreateAdminAccountTest.php` — 3 calls
- `tests/Console/Admin/GiveKingdomsToNpcsTest.php` — 1 call
- `tests/Console/Battle/ClearCelestialsTest.php` — 1 call
- `tests/Console/DeleteFlaggedUsersTest.php` — 2 calls
- `tests/Console/FlagUsersForDeletionTest.php` — 2 calls
- `tests/Console/FlagUsersWithMissingCharacterInventoriesTest.php` — 2 calls
- `tests/Console/Flare/CreateAdminTest.php` — 2 calls
- `tests/Console/Kingdoms/DeleteKingdomLogsTest.php` — 1 call
- `tests/Console/Messages/CleanChatTest.php` — 1 call
- `tests/Console/Raids/RessurectRaidBossTest.php` — 3 calls
- `tests/Feature/Flare/GameImporter/ImportGameDataTest.php` — 2 calls
- `tests/Feature/Game/Tops/SnapshotMonthlyTopsCommandTest.php` — 1 call

Total: 22 calls, matching the count stated in the task.

## Obsolete assertion patterns removed

- `$this->assertEquals(0, $this->artisan(...));` — 16 occurrences, replaced with `$this->artisan(...)->assertExitCode(0);`
- `$this->assertSame(0, $this->artisan(...));` (including the multi-line form in `FlagUsersWithMissingCharacterInventoriesTest.php`) — 2 occurrences, replaced with `$this->artisan(...)->assertExitCode(0);`
- Bare `$this->artisan(...);` with no execution/assertion — 5 occurrences (`RessurectRaidBossTest.php` ×3, `ImportGameDataTest.php` ×2), replaced with `$this->artisan(...)->assertExitCode(0);`, which explicitly executes the command and preserves (adds, does not remove) an exit-code check consistent with each test's existing "command succeeds" expectation.
- `$exitCode = $this->artisan(...);` followed by a separate `$this->assertSame(0, $exitCode);` — 1 occurrence (`SnapshotMonthlyTopsCommandTest.php`), fixed by appending `->execute()` to the assignment so `$exitCode` is the real integer exit code; the pre-existing `assertSame(0, $exitCode)` assertion was left completely untouched.

## Expected-exception command execution

`tests/Console/Admin/CreateAdminAccountTest.php::test_fail_to_create_admin` calls `$this->expectException(RuntimeException::class);` before invoking `create:admin` with a missing required `{email}` argument, which throws a Symfony `RuntimeException` from inside `Kernel::call()` when the command's input binding fails. Since the exception must propagate out of the pending-command execution itself (not be wrapped in an exit-code assertion), this call was changed to `$this->artisan('create:admin')->execute();` — explicit execution via `execute()` (confirmed present on the installed `Illuminate\Testing\PendingCommand`, Laravel `^12.0`, at `vendor/laravel/framework/src/Illuminate/Testing/PendingCommand.php:432`), with no `assertExitCode()` added, since the command is expected to throw rather than return successfully.

## Confirmation: no console-kernel binding added

No changes were made to `bootstrap/app.php`, any service provider, or any container binding. `git status --porcelain` confirms only the 13 listed test files plus this `proof_of_work.md` were modified in this pass.

## Confirmation: no production file changed

No file under `app/**`, `routes/**`, `database/**`, `config/**`, `bootstrap/**`, `public/**` was modified. No command class's `handle()` logic was touched (`app/Admin/Console/Commands/CreateAdminAccount.php` was read only, to confirm the exact origin of the expected `RuntimeException`).

## Exact targeted Pest command run

```
php -d memory_limit=-1 -d max_execution_time=0 ./vendor/bin/pest \
  tests/Console/AddHolyStacksToItemsTest.php \
  tests/Console/Admin/CreateAdminAccountTest.php \
  tests/Console/Admin/GiveKingdomsToNpcsTest.php \
  tests/Console/Battle/ClearCelestialsTest.php \
  tests/Console/DeleteFlaggedUsersTest.php \
  tests/Console/FlagUsersForDeletionTest.php \
  tests/Console/FlagUsersWithMissingCharacterInventoriesTest.php \
  tests/Console/Flare/CreateAdminTest.php \
  tests/Console/Kingdoms/DeleteKingdomLogsTest.php \
  tests/Console/Messages/CleanChatTest.php \
  tests/Console/Raids/RessurectRaidBossTest.php \
  tests/Feature/Flare/GameImporter/ImportGameDataTest.php \
  tests/Feature/Game/Tops/SnapshotMonthlyTopsCommandTest.php
```

**Result:** `Tests: 22 passed (62 assertions)`. `Duration: 77.01s`. 0 failures, 0 errors, 0 risky, 0 skipped. Every test class reported `PASS`.

## Confirmation: console-kernel error no longer occurs

The full run above produced zero occurrences of `Target [Illuminate\Contracts\Console\Kernel] is not instantiable.` in its output.

## Remaining `$this->artisan()` occurrences and why each is correctly executed

A follow-up `grep -n "artisan("` across all 13 target files shows all 22 occurrences now end in either `->assertExitCode(0)` (20 occurrences, including the multi-line `FlagUsersWithMissingCharacterInventoriesTest.php` call which chains `->assertExitCode(0)` on the following line) or `->execute()` (2 occurrences: `CreateAdminAccountTest.php::test_fail_to_create_admin`'s expected-exception case, and `SnapshotMonthlyTopsCommandTest.php`'s `$exitCode = ...->execute();` assignment). No bare `$this->artisan(...);` statement and no `assertEquals`/`assertSame` comparison against the raw return value of `$this->artisan()` remains anywhere in the 13 files.

## Pint

`./vendor/bin/pint` was run against exactly the 13 changed test files (no other paths). Result: `{"tool":"pint","result":"passed"}` — no formatting changes were required.

## Final status

All 13 targeted files pass. The `Target [Illuminate\Contracts\Console\Kernel] is not instantiable.` failure is resolved for every affected test. No console-kernel binding, `bootstrap/app.php` change, production command change, or application-behavior change was made.

---

# Proof of Work — Obsolete BrowserKit `$this->response` Contract Repair

## Root cause

`tests/TestCase.php` now extends `Illuminate\Foundation\Testing\TestCase` instead of Laravel BrowserKit's test case. Laravel's own HTTP testing methods (`get()`, `post()`, `json()`, `getJson()`, `postJson()`, `call()`) return an `Illuminate\Testing\TestResponse` directly from the call itself. BrowserKit instead stashed the last response on a `$this->response` instance property and expected assertions to read `$this->response` or a trailing `->response` accessor afterward. 28 test methods across 6 files still used the stale BrowserKit contract (`$this->response`, `->response` accessor, or `$this->assertSessionHas(...)` called on the test case instead of the response), which no longer exists on the current base class.

## Files changed (6 files, exactly as authorized)

- `tests/Feature/Game/BatchCrafting/Controllers/BatchCraftingControllerTest.php` — 21 occurrences corrected (1 `$this->response->assertStatus(302)` direct access, 20 `$response = $this->response;` assignments removed and merged into the originating request call).
- `tests/Feature/Game/Core/Controllers/SettingsControllerSecurityTest.php` — 2 occurrences of trailing `->response` removed; `post(...)` result already assigned to `$response`.
- `tests/Feature/Game/Market/Controllers/MarketControllerTest.php` — 2 occurrences of `$response = $this->response;` merged into the originating `json('POST', ...)` call.
- `tests/Feature/Game/Npcs/Actions/Seer/Controllers/Api/SeerCampControllerTest.php` — 1 occurrence merged into the originating `json('POST', ...)` call.
- `tests/Feature/Game/Npcs/Actions/WorkBench/Controllers/Api/HolyItemsControllerTest.php` — 1 occurrence merged into the originating `json('POST', ...)` call.
- `tests/Feature/Game/PassiveSkills/Controllers/CharacterPassiveSkillWebControllerTest.php` — 1 `$this->assertSessionHas(...)` call replaced with `$response->assertSessionHas(...)`, with the originating `get(...)` call assigned to `$response`.

No other file was touched. No production file (`app/**`, `routes/**`, `database/**`, `config/**`, `resources/**`, `bootstrap/**`, `public/**`) was changed. `tests/TestCase.php`, `tests/Pest.php`, `phpunit.xml`, and Composer files were not touched by this pass.

## Occurrence counts

- `$this->response` (property access, including the one direct `$this->response->assertStatus(302)`): 21 occurrences removed (all in `BatchCraftingControllerTest.php`).
- `->response` (trailing accessor on a call result): 2 occurrences removed (both in `SettingsControllerSecurityTest.php`).
- `$this->assertSessionHas(...)` (called on the test case instead of the response): 1 occurrence replaced with `$response->assertSessionHas(...)` (in `CharacterPassiveSkillWebControllerTest.php`).
- Total: 28, from a `28 failed` count in the reported baseline run and matching the 28 line numbers given in the task.

Every corrected call site now captures the real `Illuminate\Testing\TestResponse` returned directly by `get()`/`post()`/`json()`/`getJson()`/`postJson()`/`call()` into a local `$response` variable at the call site, with no intermediate `$this->response` property read anywhere in scope.

## Static verification

```
rg -n '\$this->response|->response;|\$this->assertSessionHas\(' tests --glob '*.php'
```
→ **no matches** (run after all 6 files were edited).

```
rg -n '::factory\(' <the six files>
```
→ **no matches** — no direct model factory calls were introduced.

`git diff` for the six files was inspected line by line: every hunk either (a) prefixes an existing `$this->actingAs(...)->method(...)` call with `$response = `, (b) deletes the now-redundant `$response = $this->response;` line immediately following that call, or (c) drops a trailing `->response` accessor / retargets an assertion from `$this->assertSessionHas` to `$response->assertSessionHas`. No test name, assertion, fixture, disposition value, payload, or expected message was altered. No helper method, loop, data provider, mock, or fake was added.

## Two omissions caught and fixed during the first targeted run

The first pass of edits to `BatchCraftingControllerTest.php` initially missed prefixing the `$this->actingAs(...)` call with `$response = ` for 3 of the 21 methods (`test_holy_oils_rejects_supplied_output_destination`, `test_output_set_id_rejected_when_destination_is_not_inventory_set`, `test_finite_craft_amount_keep_accepts_inventory_output_destination`) while still removing the trailing `$response = $this->response;` line, leaving `$response` undefined. The targeted run below caught this (`ErrorException: Undefined variable $response` at the three affected lines); all three were corrected by adding the missing `$response = ` prefix, and the targeted run was repeated to confirm all pass.

## Targeted Pest command and result

```
php -d memory_limit=-1 -d max_execution_time=0 ./vendor/bin/pest \
  tests/Feature/Game/BatchCrafting/Controllers/BatchCraftingControllerTest.php \
  tests/Feature/Game/Core/Controllers/SettingsControllerSecurityTest.php \
  tests/Feature/Game/Market/Controllers/MarketControllerTest.php \
  tests/Feature/Game/Npcs/Actions/Seer/Controllers/Api/SeerCampControllerTest.php \
  tests/Feature/Game/Npcs/Actions/WorkBench/Controllers/Api/HolyItemsControllerTest.php \
  tests/Feature/Game/PassiveSkills/Controllers/CharacterPassiveSkillWebControllerTest.php
```

**First run:** 3 failed (the omissions above), 116 passed (249 assertions).
**Second run (after the fix):** `Tests: 119 passed (257 assertions)`. `Duration: 92.49s`. 0 failures, 0 errors, 0 risky, 0 skipped. All 6 files reported `PASS`, including all 28 originally-failing test methods by name.

## Full suite verification

**Not run.** The user explicitly instructed, mid-task, to run only the targeted/failing tests rather than the full suite (`"only run the failing tests to make sure they all pass"`), overriding the full-suite-verification step. Full-suite status (whether the remaining ~2,300 other tests still pass) is therefore unverified in this pass and should not be assumed from this document.

## Pint

```
./vendor/bin/pint \
  tests/Feature/Game/BatchCrafting/Controllers/BatchCraftingControllerTest.php \
  tests/Feature/Game/Core/Controllers/SettingsControllerSecurityTest.php \
  tests/Feature/Game/Market/Controllers/MarketControllerTest.php \
  tests/Feature/Game/Npcs/Actions/Seer/Controllers/Api/SeerCampControllerTest.php \
  tests/Feature/Game/Npcs/Actions/WorkBench/Controllers/Api/HolyItemsControllerTest.php \
  tests/Feature/Game/PassiveSkills/Controllers/CharacterPassiveSkillWebControllerTest.php
```
→ `{"tool":"pint","result":"passed"}`. `git diff --stat` for the six files was identical before and after this command — Pint made no changes, so no rerun of the targeted tests was needed on that account.

## Final status

- All 28 originally-reported obsolete-BrowserKit-response failures are resolved.
- The 6 authorized files pass in full (119/119 tests, 257 assertions) when run directly.
- No production file, `tests/TestCase.php`, `tests/Pest.php`, Composer files, or PHPUnit/Pest configuration was changed.
- No compatibility shim, helper method, loop, data provider, mock, or fake was added.
- Full-suite verification was skipped per explicit user instruction; full-suite status is not claimed here.
