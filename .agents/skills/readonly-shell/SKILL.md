---
name: readonly-shell
description: Use for safe read-only repository inspection before code changes.
---

# Read-Only Shell Permissions

Read-only inspection commands may be used without modifying repository state.

Allowed examples:

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

During autonomous implementation/review work, do not use git for inspection unless the user explicitly requested a git operation. Use filesystem inspection tools instead.

Do not use an inspection command in a way that writes files, mutates the database, changes git state, installs dependencies, starts services, or executes application behavior unless the task and applicable skills explicitly authorize that action.

Before editing, inspect the relevant production path, tests, configuration, and existing abstractions needed to make a factual change.
