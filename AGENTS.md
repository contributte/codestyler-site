# AGENTS.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is `contributte/codestyler` - a visual PHP_CodeSniffer playground web application. Users can browse 160+ sniffs, check their code against selected rules, and generate custom rulesets.

## Tech Stack

- **Backend**: Nette Framework (via contributte/nella)
- **Frontend**: Alpine.js, TailwindCSS, CodeMirror 6
- **Build**: Vite
- **Analysis**: squizlabs/php_codesniffer, slevomat/coding-standard

## Commands

```bash
# Install dependencies
make install

# Build frontend assets
make build

# Run development servers
make dev

# Run PHP server only
make server

# Run Vite dev server only
make assets

# Run tests
make tests

# Check/fix code style
make cs
make csf

# Clear caches
make clean
make sniffs-cache
```

## Architecture

### Directory Structure

```
app/
├── Bootstrap.php           # Application bootstrap
├── Service/                # Business logic services
│   ├── SniffService.php    # Discovers and lists sniffs
│   ├── CodeCheckerService.php  # Runs phpcs/phpcbf
│   ├── RulesetGeneratorService.php  # Generates ruleset.xml
│   └── ShareService.php    # Handles shareable URLs
└── UI/                     # Presentation layer (MVP)
    ├── BasePresenter.php   # Base presenter class
    ├── TemplateFactory.php # Custom template factory with Vite
    ├── @Templates/         # Shared layout templates
    ├── Home/               # Landing page
    ├── Sniffs/             # Sniff catalog
    ├── Playground/         # Code playground
    └── Api/                # AJAX endpoints

assets/
├── js/
│   ├── app.ts              # Main entry point
│   ├── 3rd/                # Third-party wrappers
│   │   ├── alpine.ts       # Alpine.js setup
│   │   └── codemirror.ts   # CodeMirror setup
│   └── ui/                 # Alpine components
│       ├── playground.ts   # Playground component
│       └── sniff-browser.ts
└── css/
    └── tailwind.css        # TailwindCSS input

config/
├── config.neon             # Main Nette configuration
└── local.neon.example      # Local config template

www/
├── index.php               # Application entry point
├── .htaccess               # Apache routing
└── dist/                   # Built assets (generated)
```

### Key Services

**SniffService** - Discovers all available sniffs from PHP_CodeSniffer and Slevomat Coding Standard packages. Uses reflection to extract properties and loads examples from test fixtures.

**CodeCheckerService** - Runs phpcs/phpcbf on user-submitted code via Symfony Process. Creates temporary files for execution.

**RulesetGeneratorService** - Generates valid ruleset.xml from selected sniffs and their properties.

**ShareService** - Encodes/decodes playground state for shareable URLs using gzip compression + base64.

### Frontend Architecture

- Alpine.js handles all reactivity and state management
- CodeMirror 6 provides the code editor with PHP syntax highlighting
- TailwindCSS for styling
- Vite for bundling with dev server HMR

### Presenter Pattern

Uses Nette's MVP pattern:
- Presenters handle HTTP requests and prepare data for templates
- Templates use Latte templating engine
- AJAX handlers use `handle*` methods in presenters

## Code Style

- Uses tabs for indentation (4-space equivalent)
- Strict typing with `declare(strict_types=1)` on first line
- PSR-4 autoloading with `App\` namespace for `app/` directory

## Testing

Tests are in `tests/` directory. The existing test fixtures in `tests/Sniffs/` are used as examples for the sniff catalog.
