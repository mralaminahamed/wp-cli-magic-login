# WP-CLI Magic Login

A WP-CLI package that generates a one-time magic login URL for any WordPress
user — defaulting to the first administrator account. Built for local
development with **Laravel Valet** and **WP-CLI**.

---

## Requirements

| Dependency | Version  |
|------------|----------|
| PHP        | ≥ 7.4    |
| WP-CLI     | ≥ 2.0    |
| WordPress  | ≥ 5.0    |

---

## Installation

### 1. Install the WP-CLI package

```bash
wp package install mralaminahamed/wp-cli-magic-login
```

### 2. Install the WordPress request handler

Copy `magic-login-handler.php` into your site's must-use plugins directory:

```bash
cp vendor/mralaminahamed/wp-cli-magic-login/magic-login-handler.php \
   wp-content/mu-plugins/magic-login-handler.php
```

Or, for a Valet-managed site, you can symlink it:

```bash
ln -s ~/.wp-cli/packages/vendor/mralaminahamed/wp-cli-magic-login/magic-login-handler.php \
   wp-content/mu-plugins/magic-login-handler.php
```

> **Note:** The handler must be present so the login URL resolves correctly
> when visited in the browser.

---

## Usage

```bash
# Log in as the first administrator (opens browser automatically)
wp magic-login

# Print the URL only; do not open the browser
wp magic-login --no-launch

# Target a specific user by login
wp magic-login --user=johndoe

# Target a specific user by ID
wp magic-login --user=3

# Set a custom token expiry (seconds, default: 60)
wp magic-login --expiry=300

# Output raw URL only (useful in scripts)
wp magic-login --porcelain
```

---

## Options

| Flag            | Default         | Description                                              |
|-----------------|-----------------|----------------------------------------------------------|
| `--user`        | First admin     | User login name or numeric ID                            |
| `--expiry`      | `60`            | Token lifetime in seconds                                |
| `--no-launch`   | `false`         | Print URL without opening the browser                    |
| `--porcelain`   | `false`         | Output the raw URL only; no success message              |

---

## Security Notice

This package is intended **exclusively for local and staging environments**.
The generated token is single-use and transient-backed, but:

- Do **not** install the mu-plugin handler on a production site.
- Always restrict CLI access on staging environments.

---

## `wp mu-plugin` — Must-Use Plugin Manager

Installs, removes, and lists must-use plugins on any Valet-hosted WordPress site.
Operates entirely at the filesystem level — no WordPress bootstrap required.

### How site resolution works

| Scenario | Resolution |
|---|---|
| `--domain` omitted | Walks up from `cwd` to find `wp-config.php` |
| `--domain=mysite.test` | Reads `~/.config/valet/config.json` for parked/linked paths, then matches folder `mysite` |
| Valet config absent | Falls back to `~/Sites/<folder>` |

### install

```bash
# Install the bundled magic-login handler into the current site
wp mu-plugin install magic-login-handler

# Install into a specific Valet domain
wp mu-plugin install magic-login-handler --domain=mysite.test

# Install any custom PHP file by path
wp mu-plugin install /path/to/my-loader.php --domain=shop.test

# Overwrite an existing file
wp mu-plugin install magic-login-handler --domain=mysite.test --force
```

### remove

```bash
# Remove by filename (.php extension optional)
wp mu-plugin remove magic-login-handler

# Remove from a specific domain, skip confirmation prompt
wp mu-plugin remove magic-login-handler --domain=mysite.test --yes
```

### list

```bash
# List all mu-plugins in the current site
wp mu-plugin list

# List for a specific domain
wp mu-plugin list --domain=mysite.test

# Output as JSON
wp mu-plugin list --domain=mysite.test --format=json
```

Output columns: `file`, `size`, `modified`, `path`

### Bundled slugs

| Slug | File |
|---|---|
| `magic-login-handler` | `magic-login-handler.php` |

---

## License

MIT © [Al Amin Ahamed](https://alaminahamed.com)
