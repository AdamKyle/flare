---
name: autonomous-execution-discipline
description: Use during autonomous implementation/review-remediation tasks to complete work directly in the foreground without delegation, background waiting, wakeups, or progress interruptions unless explicitly requested.
---

# Autonomous Execution Discipline

## Do the task directly

When the user asks for an implementation/refactor/review-remediation task to be completed autonomously, perform the work directly using the available foreground tools.

Do not delegate the task to sub-agents or parallel agents unless the user explicitly requests delegation for that task.

Do not spawn additional agents merely to inspect files, run tests, review code, or verify work.

You are responsible for reading, editing, testing, and reviewing the requested work yourself.

## No background/wakeup workflow

Do not:

- background shell commands with `&`;
- start background workers solely to continue the task;
- schedule wakeups/check-ins;
- stop and wait for a later wakeup;
- sleep/poll while work remains;
- tell the user to wait for completion later.

Run required commands in the foreground, consume their result, fix failures, and continue.

## No progress interruptions

When the task explicitly requests execution without status chatter, do not interrupt the work with plans, status updates, check-ins, or partial summaries.

Continue until either:

- every requested requirement and quality gate is satisfied; or
- a genuine hard blocker makes completion impossible.

A failing test, lint rule, coverage line, or implementation defect is work to fix, not a reason to stop and report progress.

## Self-review before completion

Before declaring completion:

1. reread the applicable skills;
2. inspect every file changed in the task;
3. search the touched path for prohibited patterns from the skills;
4. verify no requested behavior was skipped;
5. run every required test/coverage/quality command;
6. fix every task-caused failure;
7. rerun gates against the final code;
8. verify proof/documentation is factual if the task requires it.

Do not stop at “mostly done.”

For backend/test work, self-review must include the prohibited-pattern audit required by `repository-code-quality-and-clean-as-you-go`. Green tests or 100% coverage never override a skill violation; completion is blocked while any unexplained prohibited match remains.

Do not claim completion while a known rule violation, uncovered task path, failing gate, stale debug code, or requested cleanup remains.

## Task-size and session-size are never blockers

When the user has supplied a complete implementation contract, continue until that contract is complete.

The following are never valid reasons to stop, defer, split the task, or report partial completion:

- the task is large;
- the task would normally take days or weeks;
- the current session has already been long;
- a convenient checkpoint has been reached;
- many files remain;
- more local file inspection is required before the next edit;
- the implementation feels risky merely because it spans several systems;
- a subset of tests is already green.

Work sequentially, one responsibility at a time. Inspect the exact local files needed for the next responsibility, implement it, verify it, then continue to the next responsibility.

Only stop when an actual technical contradiction or unavailable required dependency makes the requested implementation impossible, or when an explicitly permitted command fails and the failure cannot be corrected within the requested scope.

Do not reinterpret a large task as permission to redefine its completion boundary.

## Inspection is part of implementation, not a permission checkpoint

Read-only repository inspection does not require user confirmation. Use the `readonly-shell` skill freely while work remains.

Do not pause to ask permission to run `rg`, `grep`, `sed`, `find`, `awk`, `cat`, `head`, `tail`, `wc`, `sort`, `uniq`, `cut`, `diff`, `cmp`, `stat`, `file`, or equivalent non-destructive inspection commands.

Do not make the user repeatedly approve ordinary inspection.
