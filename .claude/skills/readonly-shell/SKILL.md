---
name: readonly-shell
description: Allows Claude to inspect files with safe read-only shell commands without asking for permission.
---

# Read-Only Shell Permissions

Claude may run read-only inspection commands without asking for permission.

Allowed commands:

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

These commands may only inspect files, directories, or git state.

Claude must not run commands that:

- execute tests
- execute application code
- modify files
- delete files
- move files
- install dependencies
- run migrations
- touch the database
- start services
- profile code

Forbidden unless explicitly approved:

- `phpunit`
- `artisan`
- `composer`
- `npm`
- `yarn`
- `pnpm`
- `mysql`
- `psql`
- `docker`
- `rm`
- `mv`
- `cp`
- `chmod`
- `chown`
- `touch`
- `truncate`
- shell redirects such as `>`
- shell redirects such as `>>`
- pipes that write files
- `tee`

Claude may edit files only when the task explicitly requires code changes and only after inspecting the relevant files.
