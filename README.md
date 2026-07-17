# WP-CLI Magic Login

A WP-CLI package that generates one-time magic login URLs for WordPress. Built for local development.

---

## Installation

```bash
wp package install mralaminahamed/wp-cli-magic-login
```

---

## Command: `wp magic-login`

Generates a one-time login URL for a WordPress user without a password, and opens
it in the browser. The companion handler mu-plugin is **installed and kept
up to date automatically** — there is no separate install step.

```bash
# Log in as the sole administrator (opens browser automatically)
wp magic-login

# Print the URL only; do not open the browser
wp magic-login --no-launch

# Target a specific user by login name, ID, or email.
# Use --login (not --user): --user is a reserved WP-CLI global that never reaches
# the command. WP-CLI's global --user is honoured as a fallback.
wp magic-login --login=johndoe
wp magic-login --login=3
wp magic-login --login=jane@example.test

# Land on a specific admin screen after login
wp magic-login --redirect=edit.php

# Custom token expiry (seconds)
wp magic-login --expiry=300

# Raw URL only (scripting)
wp magic-login --porcelain
```

| Option                | Default | Description                                                             |
|-----------------------|---------|-------------------------------------------------------------------------|
| `--login`             | current `--user`, else the sole admin | User to log in as: login name, ID, or email |
| `--expiry`            | 60      | Token lifetime in seconds                                               |
| `--redirect`          | wp-admin | Same-host path/URL to land on after login                              |
| `--no-launch`         | launch on | Print the URL without opening a browser                               |
| `--porcelain`         | false   | Output the raw URL only                                                 |
| `--no-install-server` | false   | Skip the automatic handler install/refresh                              |
| `--force`             | false   | Run on a production environment and force-refresh the handler           |

When several administrators exist and no user is given (and WP-CLI has no current
user), the command lists the administrators and asks you to pick one with `--login`.

---

## Requirements

| Dependency | Version |
|------------|---------|
| PHP        | ≥ 7.4   |
| WP-CLI     | ≥ 2.11  |
| WordPress  | ≥ 5.0   |

---

## Security Notice

This package is intended **exclusively for local and staging environments**.

- The command **refuses to run when `wp_get_environment_type()` is `production`** unless `--force` is passed.
- The generated token is single-use, transient-backed, and stored only as a hash (`wp_hash`), compared with `hash_equals()`.
- A fresh URL for a user invalidates any previous unused one.
- Always restrict CLI access on staging environments.

---

## Development

```bash
# Install dependencies
composer install

# Unit tests
composer phpunit

# Behat acceptance tests (requires MySQL)
composer prepare-tests
composer behat

# Coding standards
composer phpcs

# Full suite
composer test
```

---

## License

MIT © [Al Amin Ahamed](https://alaminahamed.com)
