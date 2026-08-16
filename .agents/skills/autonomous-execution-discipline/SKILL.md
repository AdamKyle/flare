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

Do not claim completion while a known rule violation, uncovered task path, failing gate, stale debug code, or requested cleanup remains.
