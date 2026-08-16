---
name: phpunit-ci-diagnostics
description: Use this skill when configuring GitHub Actions to execute, time, monitor, or preserve diagnostics from the PHPUnit suite.
---

# PHPUnit CI Diagnostics

## Normal test execution

The normal full-suite CI run must not run coverage.

Run the normal suite with:

`XDEBUG_MODE=off`

Xdebug may remain installed on the runner, but its runtime mode must be disabled for the ordinary PHPUnit command.

Coverage belongs in a separate explicitly requested job.

## Fresh diagnostic output

Before PHPUnit starts:

- Remove the previous CI test-results directory.
- Recreate an empty test-results directory.
- Remove a stale root JUnit file.
- Remove test-generated Laravel log files from the ephemeral runner.
- Do not reuse repository-tracked diagnostic output.
- Do not copy old local logs into the new artifact.

Every diagnostic artifact must be produced by the current GitHub run.

## Required PHPUnit output

The full-suite command must create:

- `test-results/phpunit.log`
- `test-results/phpunit-events.log`
- `test-results/junit.xml`

Use PHPUnit’s verbose event log option so the final prepared, started, errored, failed, and completed test can be identified.

Preserve normal console visibility by teeing PHPUnit output into `test-results/phpunit.log`.

## Exit status

The workflow must preserve PHPUnit’s real exit status.

Diagnostic collection and artifact upload must still run when PHPUnit:

- Passes.
- Fails.
- Errors.
- Is cancelled.
- Reaches the job timeout.

Do not convert a failing PHPUnit run into a successful workflow result.

## Heartbeat

While PHPUnit is running, write a heartbeat at least once per minute.

Each heartbeat must include:

- UTC timestamp.
- PHPUnit process identifier.
- Elapsed process time.
- CPU usage.
- Memory usage.
- Process state.
- The most recent PHPUnit event-log lines.

Stop the heartbeat process after PHPUnit exits.

Do not leave a background process running after the test step.

## Post-run diagnostics

Run post-test diagnostics with `if: always()`.

Include:

- Tail of the PHPUnit console log.
- Tail of the PHPUnit event log.
- Last identifiable test preparation and completion events.
- Process information.
- Memory information.
- Disk information.
- Existing MySQL diagnostics when the workflow already has a valid database connection command.

Do not invent database credentials or connection commands.

Reuse the workflow’s existing database configuration.

## JUnit

Do not assume the JUnit file is complete.

Before parsing it:

- Confirm it exists.
- Confirm it is non-empty.
- Confirm it can be parsed.

When it is empty or invalid, report that fact and continue collecting the remaining diagnostics.

Do not let JUnit parsing hide the real PHPUnit exit status.

## Artifacts

Upload diagnostics with `if: always()`.

Use an artifact name containing the GitHub run ID and commit SHA.

Include only output produced by the current run:

- `test-results/**`
- Current test-generated Laravel logs needed for diagnosis.

Do not upload stale repository logs.

## Suite splitting

Do not split Console, Feature, and Unit tests during the first measurement after a major suite cleanup.

Run the reduced suite once as a single serial suite so its total runtime and any serial stall can be measured.

Consider splitting only after a new reduced-suite run proves that the single suite still approaches the timeout.

## Local restrictions

When the task prohibits local PHPUnit execution:

- Edit the workflow only.
- Do not execute PHPUnit locally.
- Do not execute the CI shell block locally.
- Do not claim the workflow is proven until GitHub executes it.

## Diagnostics Must Not Modify Code With Output Statements

Diagnostic work must not add temporary `fwrite`, `echo`, `print`, `var_dump`, `dump`, `dd`, STDERR/STDOUT writes, or debug environment branches to application/test code. Use PHPUnit's diagnostic/event output and focused commands instead.
