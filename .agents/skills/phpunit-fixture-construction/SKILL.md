---
name: phpunit-fixture-construction
description: Use this skill whenever PHPUnit tests create models, characters, inventories, kingdoms, monsters, factions, automation state, or other database fixtures.
---

# PHPUnit Fixture Construction

## Core Rule

Test classes and test methods must never call Laravel model factories directly.

No retained `*Test.php` file may contain `Model::factory()` or another direct `::factory()` call.

Test classes must instead use:

1. An existing `Tests\Traits\Create*` trait for a single model.
2. An existing domain setup factory under `Tests\Setup/**`.
3. An existing management class belonging to a domain setup factory.
4. A new narrowly scoped `Create*` trait when no appropriate single-model trait exists.
5. A new or extended domain setup factory when multiple related records form a meaningful domain graph.

Direct Laravel factory calls are allowed only inside centralized fixture abstractions such as:

- `tests/Traits/Create*.php`
- `tests/Setup/**`

They are not allowed inside `*Test.php` files.

Do not import or instantiate classes from `database/factories` inside a test class.

## Existing Abstractions

Inspect and reuse existing fixture abstractions before creating another one.

Existing categories include:

- `Tests\Setup\Character\CharacterFactory`
- Character inventory, automation, kingdom, passive-skill, gem-bag, attack-data, and inventory-set management classes.
- `Tests\Setup\Monster\MonsterFactory`
- `Tests\Setup\FactionLoyalty\FactionLoyaltyFactory`
- `Tests\Traits\Create*` traits.

Do not bypass an existing abstraction because a direct model factory call is shorter.

## Single Models

For one isolated model:

- Use the appropriate existing `Create*` trait.
- Create a missing `Create<ModelName>` trait only when no suitable trait exists.
- Keep the trait focused on that model.
- Allow an attributes array.
- Return the created model.
- Follow the repository’s existing trait pattern.

Do not create generic universal helpers such as:

- `createModel`
- `makeRecord`
- `factoryFor`
- `createTestData`

## Fixture Boundaries

- `Create*` traits must create or configure fixture data.
- Fixture traits must not perform HTTP requests.
- Fixture traits must not make assertions.
- Fixture traits must not parse HTML.
- Fixture traits must not execute a complete controller flow.
- Test-flow helpers are prohibited.
- A repeated domain fixture belongs in a setup factory or management object.
- The endpoint behavior being tested remains visible in the test method.
- Do not create a full playable character merely to obtain a user.

## Domain Graphs

For related records forming a domain object:

- Use or extend a domain setup factory.
- Give methods domain-specific names.
- Keep relationship construction out of test methods.
- Do not build large graphs using repeated `relation()->create()`, raw inserts, or factory chains directly in a test.
- Do not create one enormous universal test factory.
- Keep factories and management classes scoped to their domain.

## Character Construction

Use `CharacterFactory` when a test requires a normal playable character or character-related graph.

Use `CreateCharacter` directly only when static inspection proves the test needs a deliberately bare character and the production path does not require normal inventories, bags, skills, passive skills, class ranks, weapon masteries, attack cache data, or other playable-character relationships.

Use `CharacterFactory` management APIs for:

- Inventory.
- Inventory sets.
- Equipped items.
- Gem bags.
- Automation.
- Kingdoms.
- Passive skills.
- Attack data.
- Character-related domain state.

Do not manually reproduce those relationships inside test classes.

`createBaseCharacter()` remains the normal playable-character path.

Do not globally make it minimal by default during an unexecuted cleanup.

Its optional arguments must be respected.

Pass optional arguments using names when disabling behavior, for example:

- `assignBaseSkill: false`
- `assignPassiveSkills: false`
- `createClassRanks: false`

Disable optional character components only after reading the complete production path and proving that neither the test nor an indirect collaborator requires them.

Do not assume an apparently unrelated component is unnecessary.

## `setUp()`

`setUp()` runs before every test. It does not reuse one database character across the test class and is not itself a runtime optimization.

Shared setup belongs in `setUp()` when every test in the class requires the exact same baseline. Do not repeat that baseline in every test.

Use `setUp()` only when:

- Every test in that class requires the exact same baseline.
- The setup is small and clear.
- The setup uses existing traits or setup factories.
- No scenario-specific state is included.

Allowed examples include:

- Initializing the service under test.
- Creating one common baseline character required by every test.
- Resolving one common collaborator.
- Assigning one common model required by every test.

Do not put the following in `setUp()`:

- Direct model-factory calls.
- Large relationship graphs.
- Scenario-specific records.
- Different states used by only some tests.
- Loops constructing records.
- Assertions.
- Complicated branching.
- Hidden behavior that makes individual tests difficult to understand.

Scenario-specific setup remains in its test method, but must use traits or domain setup factories.

## Lifecycle cleanup

When the existing project pattern stores shared objects in class properties and tears them down explicitly, set those properties to `null` in `tearDown()` after calling the appropriate parent lifecycle method. Follow the exact nearby repository lifecycle pattern; do not invent cleanup for transactional database records.

## Test Helpers

Do not add private, protected, or public helper methods to test classes.

Do not move complicated test setup into a test-class helper.

Existing test helper methods in retained test files must be removed by:

- Inlining small scenario-specific calls.
- Moving single-model construction to an appropriate trait.
- Moving domain construction to an appropriate setup factory or management class.

`setUp()` and `tearDown()` are the only permitted lifecycle methods.

## Jobs

Use the application dispatch path:

`JobName::dispatch($arguments);`

The configured test queue runs synchronously.

Do not:

- Instantiate a job and manually call `handle()` when production dispatches it.
- Reinitialize a job to simulate queue reconstruction.
- Add queue behavior tests that duplicate the service invoked by the job.
- Replace meaningful dispatch execution with a fake merely to assert that a job was pushed.

Keep one focused test for each materially distinct orchestration path owned by the job.

## Mocking

Follow `phpunit-mocking` for all mock decisions. Do not use mocks merely to make fixture setup easier.

## Maps

Do not create fake map image files.

Do not use:

- `Storage::fake('maps')`
- `imagecreatetruecolor()`
- `imagecolorallocate()`
- `imagefill()`
- `ob_start()`
- `imagepng()`
- `Storage::disk('maps')->put(...)`
- `imagedestroy()`

Prefer:

- The character’s existing map.
- Only the required `Location` records.
- Existing cache setup.
- Mocking the map color or tile dependency using the established project pattern.

## Fixture Acceptance Standard

After a fixture cleanup:

- Retained `*Test.php` files contain zero direct `::factory()` calls.
- Complex setup is centralized by domain.
- Tests remain readable without test-class helpers.
- Character construction uses the correct abstraction.
- Optional character components are disabled only when proven unnecessary.
