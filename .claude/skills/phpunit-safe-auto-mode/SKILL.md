---
name: phpunit-safe-auto-mode
description: Use this skill for autonomous PHPUnit cleanup tasks with strict file and command restrictions.
---

# PHPUnit Safe Auto Mode

## Writable Scope

For a test-only cleanup, files may be created, updated, or deleted only under:

- `tests/**`
- `.agents/skills/**`
- `.claude/skills/**`
- Root `proof_of_work.md`

Do not modify production application code.

Do not modify frontend source code.

Do not modify:

- `app/**`
- `resources/js/**`
- `routes/**`
- `database/**`
- `config/**`
- `bootstrap/**`
- `public/**`
- `composer.json`
- `composer.lock`
- `package.json`
- `yarn.lock`
- `phpunit.xml`
- CI workflow files
- Environment files

Production code may be read to understand behavior but must not be edited.

## Preserve Existing Work

Before editing:

- Inspect `git status`.
- Inspect the current diff.
- Treat all existing unrelated changes as user-owned.
- Do not overwrite, revert, reformat, or delete them.

After editing:

- Inspect `git status`.
- Inspect the final diff.
- Confirm every new change belongs to the allowed scope.
- Preserve pre-existing changes exactly.

## Read-Only Commands

Read-only inspection commands are allowed:

- `sed`
- `grep`
- `rg`
- `cat`
- `find`
- `ls`
- `pwd`
- `head`
- `tail`
- `wc`
- `stat`
- `git status`
- `git diff`
- `git grep`

Read-only pipes are allowed only when they do not write a file.

Do not use shell redirection, `tee`, scripts, or shell commands to edit files.

Use the editing tools provided by the environment.

## Forbidden Commands

Do not run:

- PHPUnit.
- Any test command.
- Coverage.
- Artisan.
- Composer.
- PHP scripts.
- Application code.
- Profilers.
- Benchmarks.
- Migrations.
- Seeders.
- Database clients.
- Docker or Docker Compose.
- Servers.
- Queue workers.
- Dependency installation.
- Build commands other than the one exact validation chain explicitly authorized by the task.
- Generated cleanup scripts.
- Python, Perl, Ruby, Node, or shell scripts created to rewrite the tests.

## Forbidden Git Operations

Do not run:

- `git add`
- `git commit`
- `git push`
- `git pull`
- `git fetch`
- `git reset`
- `git restore`
- `git checkout`
- `git clean`
- `git stash`
- `git rebase`
- `git merge`
- `git cherry-pick`
- `git revert`
- Any force operation.
- Any branch-changing operation.
- Any command that discards work.

Only the explicitly listed read-only git commands are allowed.

## Task-Specific Validation Exception

When the task explicitly requires this exact validation chain, it is allowed once at the end:

`yarn lint && yarn type-check && yarn cleanup && yarn unused-files-check && ./vendor/bin/pint`

Do not run any portion separately.

Do not run the chain more than once.

Do not substitute another command.

Do not run it before the repository changes are complete.

Because `yarn cleanup` and Pint can write files:

1. Record the pre-command git status and diff.
2. Run the exact chain once.
3. Inspect the post-command status and diff.
4. Retain formatting changes only in allowed test and skill files.
5. Manually restore accidental new changes to production or frontend files using the editor.
6. Do not use a forbidden git command to restore them.
7. Do not overwrite pre-existing user changes.
8. Do not rerun the validation chain after manual restoration.

If the chain fails, do not invent a passing result.

Record the exact failing command and output in `proof_of_work.md`.

## No Test Claims

When tests are forbidden:

- Do not say tests passed.
- Do not say the suite is working.
- Do not claim runtime improvements.
- Do not claim coverage percentages.
- State that all PHPUnit execution remains unverified by instruction.

## Completion

Complete the entire requested static cleanup before returning.

Do not return progress updates as the final result.
