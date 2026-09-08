---
name: phpunit-business-behavior-testing
description: Use whenever PHPUnit tests are created, reviewed, reduced, or changed to prove that every retained test asserts application-owned behavior rather than framework, package, constructor, type-membership, or coverage-only structure.
---

# PHPUnit Business Behavior Testing

## Business-value gate

Before retaining, adding, or rewriting a test, answer all four questions from the production code:

1. What application-owned behavior or contract does this test prove?
2. What public production path executes that behavior?
3. What observable result would differ if the application rule were broken?
4. Which layer owns the rule and is therefore the authoritative test layer?

If those questions cannot be answered with an application-owned rule, the test does not belong in the suite.

Coverage alone is not a business reason to keep a test.

## Valuable assertions

Prefer assertions about observable application outcomes such as:

- returned domain values or transformed API data;
- persisted business state;
- allowed/blocked ownership or authorization outcomes;
- currency/inventory/XP/progression mutations;
- validation or rejection specific to application rules;
- idempotency, retries, recovery, cancellation, or completion;
- required dispatch/broadcast/logging side effects owned by the subject;
- normalized import/export row content owned by project code;
- meaningful optional/conditional transformer output.

`assertInstanceOf()` is acceptable only when the concrete type itself is an application-owned observable contract whose selection is the behavior under test. It must not be used merely to prove that a class implements a Laravel/package interface or that a constructor returned the class that was instantiated.

## Structural/package-only tests are prohibited

Delete or do not create tests whose only proof is any of the following:

- `new Foo()` is an instance of `Foo`;
- an export class is an instance of a Maatwebsite `Export` concern/interface;
- an import class is an instance of a Maatwebsite `Import` concern/interface;
- a class implements a framework/package interface when no application behavior depends on runtime selection of that interface;
- a Laravel relationship method returns the framework relationship class;
- a factory produces the model it declares;
- constructor promotion assigned dependencies;
- a service provider resolves a concrete class;
- an enum case equals the scalar written in the enum declaration;
- a framework route/container/validator behaves as documented without an application-specific rule.

These tests create assertion counts without proving business behavior.

## Excel/import/export testing

Excel package IO and package concern membership are boundaries, not business logic.

For modern Admin Excel code:

- test project-owned row normalization and mapping in the owning sheet/service class;
- test meaningful valid, invalid, compatibility, identity/update, and failure branches;
- mock the Excel facade/download/import boundary when a controller/orchestrator's delegation is application behavior;
- never instantiate every import/export adapter solely to assert a Maatwebsite concern/interface;
- never perform real workbook IO merely to obtain line coverage when the project convention is to mock that boundary.

If an adapter contains no application-owned behavior beyond package wiring, it normally needs no dedicated unit test.

## One owner, one authoritative rule

Do not duplicate the same business permutation across service, controller, job, listener, and transformer tests.

Use:

- service tests for business rules and state transitions;
- request/controller tests for authentication, authorization, application-specific validation boundary, response shape/status, and one successful delegation path;
- transformer tests for meaningful transformation/conditional output;
- sheet/service tests for import/export mapping rules;
- jobs/listeners/events only for behavior uniquely owned by those layers.

An upper-layer integration test may prove wiring, but it must not restate the full lower-layer decision matrix.

## Coverage integrity

100% coverage is acceptable only when meaningful tests naturally execute the lines/branches that matter.

Never preserve or add a structural/framework-only test solely because deleting it lowers coverage.

When a coverage-only line has no application behavior worth asserting:

- use the repository's established coverage-ignore policy where explicitly allowed; or
- leave the factual coverage gap and report it if the task requires reporting;
- do not invent a fake behavioral test.

## Review record

For every deleted test, the deletion reason must be factual:

- duplicated authoritative behavior;
- same production branch with equivalent inputs;
- framework/package-only structure;
- constructor/property/type-membership assertion;
- no application-owned observable outcome;
- superseded implementation-detail coverage.

Never delete a unique business boundary merely to reduce the test count.
