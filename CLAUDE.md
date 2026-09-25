# Working Rules

These rules are absolute. They apply to every prompt and every tool, including Bash.

## Stay inside the project directory

- Never read, search, list, edit, or run commands against any path outside `/home/adam/Documents/flare`, for any reason.
- This includes `~/.claude`, `/tmp`, other projects, and system paths. Use `vendor/` and `node_modules/` inside the project when framework or package source is needed.

## Work without asking

- Read-only inspection inside the project (`ls`, `cat`, `head`, `tail`, `wc`, `grep`, `rg`, `find`, `sed -n`, read-only `git`) never needs permission. Just run it.
- Editing files, creating files, and creating directories inside the project to carry out the prompt never needs permission.
- Do not stop to ask whether to continue. Do the work until it is done.
- Keep every Bash command simple enough to be checked automatically, so it never triggers a permission prompt:
  - Use the Read tool to read files and the Edit/Write tools to change them, instead of shell scripts.
  - No heredocs, no `python3 -c`, no Python/PHP fed through stdin, no `cd ... &&` chains, no `$(...)` substitution, no `2>&1` redirection tricks.
  - Run one plain command per call, with paths relative to the project root.
  - Only chain with `&&` when the prompt gives that exact chain, such as the quality-gate command.

## Never do without explicit permission

- Never delete a file or directory unless the prompt tells you to delete it.
- Never run a git command that changes state: no `commit`, `push`, `pull`, `fetch`, `merge`, `rebase`, `reset`, `checkout`, `switch`, `restore`, `stash`, `cherry-pick`, `revert`, `tag`, `clean`, `add`, `rm`, `mv`, or branch create/delete. Read-only git (`status`, `log`, `diff`, `show`, `blame`, `grep`, `branch` listing) is fine.

## Never touch the database

- Never access a database directly (`mysql`, `psql`, `sqlite3`, `php artisan tinker`, `php artisan db*`, raw queries).
- Never run `php artisan migrate` or any `migrate:*` command, under any circumstances.
- Creating migration files is allowed. Running the test suite is allowed; tests run migrations themselves.
