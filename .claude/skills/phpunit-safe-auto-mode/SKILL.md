---
name: phpunit-safe-auto-mode
description: Use this skill for autonomous PHPUnit cleanup tasks with strict file and command restrictions.
---

# PHPUnit Safe Auto Mode

## Writable Scope

For a test-only cleanup, files may be created, updated, or deleted only under:

- `tests/**`
- Root `proof_of_work.md`

Repository skill files are governing inputs and are never writable during a test/application cleanup task. Skill maintenance must be a separate explicit task.

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

Treat all unrelated repository files/changes as user-owned. Do not overwrite, revert, reformat, or delete them. Inspect only the files required by the task and preserve unrelated work exactly.

## Read-Only Commands

Read-only filesystem inspection commands are allowed:

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
- `diff`
- `cmp`

Do not run any git command during safe auto mode.

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
- PHP scripts other than the mandatory Pint quality gate.
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
- Build commands other than repository quality gates or a build explicitly required by the task.
- Generated cleanup scripts.
- Python, Perl, Ruby, Node, or shell scripts created to rewrite the tests.

## Git is prohibited

Do not run the `git` executable at all during safe auto mode. This includes read-only commands and write commands. Use filesystem inspection tools instead.

## Mandatory repository quality gates

The repository-wide quality gates in `repository-code-quality-and-clean-as-you-go` are required after code changes:

```bash
yarn lint && yarn type-check && yarn cleanup && yarn unused-files-check && ./vendor/bin/pint
```

These commands may format files. Inspect repository state before and after, preserve pre-existing user changes, and retain only task-related formatting changes.

Do not install missing dependencies to make the gates runnable unless explicitly instructed. If a gate cannot run, report the exact blocker.

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
