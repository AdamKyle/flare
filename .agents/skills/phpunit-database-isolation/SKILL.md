---
name: phpunit-database-isolation
description: Use this skill whenever PHPUnit tests create, delete, reset, or isolate database records.
---

# PHPUnit Database Isolation

## `RefreshDatabase`

Keep `Illuminate\Foundation\Testing\RefreshDatabase` for database-backed tests.

The game creates large connected data graphs and requires reliable test isolation.

Do not remove `RefreshDatabase` merely to make a test appear faster.

Add `RefreshDatabase` to a retained database-backed test if it is missing and the test mutates the database.

## Rollback Ownership

Laravel’s test transaction owns database isolation.

Do not manually delete every model before Laravel rolls the transaction back.

Do not scan model directories during teardown.

Do not issue count queries for every model.

Do not load all model records and delete them individually.

Do not disable foreign-key checks as part of global test teardown.

Do not duplicate the work already performed by `RefreshDatabase`.

## Truncation

Do not call `truncate()` anywhere under `tests/**`.

MySQL truncation is not transaction-safe and can interfere with Laravel’s transaction lifecycle.

This prohibition includes:

- Test methods.
- `setUp()`.
- `tearDown()`.
- Test traits.
- Test setup factories.
- Test management classes.
- The base `Tests\TestCase`.

When a test genuinely needs an existing table emptied during the current transaction:

- Prefer relying on `RefreshDatabase`.
- Remove the manual reset when it is unnecessary.
- Use transactional `delete()` only when the scenario specifically requires the table empty before continuing in that same test.

Do not replace truncation with broad table-by-table deletion.

## Database definition statements

Do not execute database-definition statements under `tests/**`.

This includes:

- `ALTER TABLE`
- `CREATE TABLE`
- `DROP TABLE`
- `RENAME TABLE`
- Test-side schema mutations through `Schema::create(...)`
- Test-side schema mutations through `Schema::table(...)`
- Test-side schema drops or renames
- `DB::statement(...)` used for schema mutation
- `DB::unprepared(...)` used for schema mutation

MySQL database-definition statements can implicitly commit Laravel’s active test transaction.

This can force `RefreshDatabase` to rebuild the database for a later test.

A test must not modify the schema to manufacture a state prohibited by the current production schema.

When a test requires a value that the current schema forbids:

- Confirm whether that state remains valid production behavior.
- Remove an obsolete impossible-state test when the production schema deliberately prohibits it.
- Do not weaken the schema from a test.
- Do not alter migrations merely to satisfy an obsolete test.

A database-isolation audit is incomplete while any test-side truncation or database-definition statement remains.

## Base Test Case

The base `Tests\TestCase` must not perform global model cleanup.

Its teardown may release mocks and in-memory test objects before delegating to the parent teardown.

Database rollback remains Laravel’s responsibility.

## Session Records

When a test specifically requires a single current session:

- Use the existing character/session fixture abstraction.
- Remove old transactional session rows using `delete()` only when required.
- Insert only the session record needed by the scenario.
- Do not truncate the sessions table.

## Assertions

Do not manually clean database records after assertions.

Allow the test transaction to roll back.

## Performance Claims

Removing redundant cleanup and truncation is structurally correct, but do not claim a measured speed improvement unless tests or profiling were actually run.

When execution is prohibited, record the cleanup and state that the performance effect remains unverified.
