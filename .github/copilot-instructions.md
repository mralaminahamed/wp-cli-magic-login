# WP-CLI Magic Login Package

A WP-CLI package providing magic login URL generation and must-use plugin management for Laravel Valet sites.

**Always reference these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.**

## Package Commands

### `wp magic-login`

Generate a one-time login URL for an admin account.

- Usage: `wp magic-login <user> [--url=<url>]`
- Creates temporary login token valid for single use
- Supports custom redirect URL via `--url` parameter

### `wp mu-plugin`

Install, remove, and list must-use plugins on Valet sites.

- `wp mu-plugin install <plugin>` - Install a must-use plugin
- `wp mu-plugin uninstall <plugin>` - Remove a must-use plugin
- `wp mu-plugin list` - List installed must-use plugins

## Development Commands

- `composer install` - Install PHP dependencies
- `php -l *.php` - Syntax check PHP files
- `composer dump-autoload` - Regenerate autoload files

## Code Standards

- PHP 7.4+ compatibility
- PSR-4 autoloading
- WordPress coding standards
- Use `AlAminAhamed\WpCli\MagicLogin\` namespace

## Project Structure

```
├── magic-login.php          # Main entry point
├── magic-login-handler.php  # Login request handler
├── src/
│   ├── MagicLoginCommand.php # magic-login command
│   └── MuPluginCommand.php   # mu-plugin command
├── composer.json            # Package configuration
└── README.md                 # Documentation
```
