# WP-CLI Magic Login

A WP-CLI package that generates one-time magic login URLs for WordPress. Built for local development.

---

## Installation

```bash
wp package install mralaminahamed/wp-cli-magic-login
```

---

## Commands

### `wp magic-login install`

Installs the magic-login handler mu-plugin to the WordPress site.

```bash
# Install the handler to the current site
wp magic-login install

# Overwrite if already installed
wp magic-login install --force
```

---

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
