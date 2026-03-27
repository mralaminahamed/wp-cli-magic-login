# Git Commit Instructions for WP-CLI Magic Login Package

Consistent commit messages improve readability and changelog generation.

## 1. Format (Conventional Style)

```
<type>(<optional-scope>): <short imperative summary

<optional body>

<optional footer>
```

- Summary: ≤ 72 chars, imperative, no trailing period.
- Wrap body lines at ~100 chars.
- Separate sections with blank lines.

## 2. Allowed Types

| Type     | Purpose                            | Examples                                       |
| -------- | ---------------------------------- | ---------------------------------------------- |
| feat     | New user-facing feature            | feat(magic-login): add token expiry option    |
| fix      | Bug fix                            | fix(mu-plugin): correct plugin path handling   |
| perf     | Performance improvement            | perf: optimize token generation                |
| refactor | Code change w/o feature/bug impact | refactor: extract validation logic             |
| docs     | Documentation only                 | docs: update README                            |
| test     | Tests added/updated                | test: add command tests                        |
| chore    | Repo maintenance (no src impact)  | chore: update dependencies                     |
| build    | Build system / tooling             | build: update composer.json                    |
| style    | Formatting / whitespace (no logic) | style: fix code formatting                     |

(Use one primary type; secondary concerns go in body.)

## 3. Scopes (Optional)

Common scopes: magic-login, mu-plugin, handler, command.

Use lowercase; add new scopes sparingly.

## 4. Breaking Changes

- Start a body line with `BREAKING CHANGE:` followed by explanation & migration steps.
- Optionally append `!` after type/scope (e.g., `feat(magic-login)!:`) – still include the body note.

Example:

```
feat(magic-login)!: change token expiry behavior

BREAKING CHANGE: token_expiry option now accepts seconds instead of minutes.
```

## 5. Referencing Issues & PRs

Footer lines:

- `Closes #123`
- `Refs #456`

## 6. Examples

```
feat(magic-login): add redirect URL support

Allows specifying custom redirect after login via --url parameter.

fix(mu-plugin): handle missing plugin directory

Creates plugin directory if it doesn't exist before installation.

perf: cache token validation result

Reduces redundant database queries for token verification.

refactor: extract Token_Generator class

No behavior change; improves testability and separation of concerns.

docs: document new --url parameter
```

## 7. Commit Hygiene Checklist

- PHP syntax check passes (`php -l *.php`).
- No debug output.
- Inputs validated & output escaped.
- Tests added/updated if applicable.

## 8. Anti-Patterns

Avoid: `fix stuff`, `update code`, past tense (Added/Fixes), ticket-only messages, multi-unrelated changes.

## 9. When Unsure

Default: feat (new behavior), fix (defect), refactor (internal), chore (maintenance).

---

Following these conventions keeps the history clean and searchable.
