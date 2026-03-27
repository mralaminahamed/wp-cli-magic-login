# WP-CLI Magic Login

A WP-CLI package that provides magic login URL generation and must-use plugin management for local WordPress development with Laravel Valet.

---

## Installation

```bash
wp package install mralaminahamed/wp-cli-magic-login
```

This registers two commands: `wp magic-login` and `wp mu-plugin`.

---

## Commands

### `wp magic-login`

Generates a one-time login URL for any WordPress user without requiring a password.

```bash
# Log in as the first administrator (opens browser automatically)
wp magic-login

# Print the URL only; do not open the browser
wp magic-login --no-launch

# Target a specific user by login name or ID
wp magic-login --user=johndoe
wp magic-login --user=3

# Set a custom token expiry (seconds)
wp magic-login --expiry=300

# Output raw URL only (useful in scripts)
wp magic-login --porcelain
```

| Option       | Default | Description                              |
|--------------|---------|------------------------------------------|
| `--user`     | first admin | User login name or numeric ID        |
| `--expiry`   | 60       | Token lifetime in seconds              |
| `--no-launch`| false    | Print URL without opening the browser   |
| `--porcelain`| false    | Output raw URL only                     |

---

### `wp mu-plugin`

Manages must-use plugins on Valet-hosted WordPress sites. Operates at the filesystem level — no WordPress bootstrap required.

#### Site Resolution

| Scenario | Resolution |
|----------|------------|
| `--domain` omitted | Walks up from `cwd` to find `wp-config.php` |
| `--domain=mysite.test` | Reads Valet config for parked/linked paths |
| Valet config absent | Falls back to `~/Sites/<folder>` |

#### `wp mu-plugin install`

```bash
# Install the bundled magic-login handler into current site
wp mu-plugin install magic-login-handler

# Install into a specific Valet domain
wp mu-plugin install magic-login-handler --domain=mysite.test

# Install any custom PHP file by path
wp mu-plugin install /path/to/my-plugin.php --domain=shop.test

# Overwrite an existing file
wp mu-plugin install magic-login-handler --force
```

#### `wp mu-plugin remove`

```bash
# Remove by filename (.php extension optional)
wp mu-plugin remove magic-login-handler

# Remove from a specific domain, skip confirmation
wp mu-plugin remove magic-login-handler --domain=mysite.test --yes
```

#### `wp mu-plugin list`

```bash
# List all mu-plugins in current site
wp mu-plugin list

# List for a specific domain
wp mu-plugin list --domain=mysite.test

# Output as JSON
wp mu-plugin list --format=json
```

Output columns: `file`, `size`, `modified`, `path`

---

## Requirements

| Dependency | Version |
|------------|---------|
| PHP        | ≥ 7.4   |
| WP-CLI     | ≥ 2.0   |
| WordPress  | ≥ 5.0   |

---

## Security Notice

This package is intended **exclusively for local and staging environments**.

- Do **not** install the magic-login handler on production sites
- The generated token is single-use and transient-backed
- Always restrict CLI access on staging environments

---

## License

MIT © [Al Amin Ahamed](https://alaminahamed.com)
