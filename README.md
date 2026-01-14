![](https://heatbadger.now.sh/github/readme/contributte/codestyler/)

<p align=center>
	<a href="https://github.com/contributte/codestyler/actions"><img src="https://badgen.net/github/checks/contributte/codestyler/master"></a>
	<a href="https://github.com/contributte/codestyler"><img src="https://badgen.net/github/license/contributte/codestyler"></a>
	<a href="https://bit.ly/ctteg"><img src="https://badgen.net/badge/support/gitter/cyan"></a>
	<a href="https://bit.ly/cttfo"><img src="https://badgen.net/badge/support/forum/yellow"></a>
	<a href="https://contributte.org/partners.html"><img src="https://badgen.net/badge/sponsor/donations/F96854"></a>
</p>

<p align=center>
Website 🚀 <a href="https://contributte.org">contributte.org</a> | Contact 👨🏻‍💻 <a href="https://f3l1x.io">f3l1x.io</a> | Twitter 🐦 <a href="https://twitter.com/contributte">@contributte</a>
</p>

# Codestyler

Visual PHP_CodeSniffer playground. Browse 160+ sniffs, check your code, and generate custom rulesets.

## Features

- **Sniff Catalog** - Browse all available PHP_CodeSniffer sniffs with descriptions and examples
- **Code Playground** - Paste PHP code, select rules, see errors and auto-fixed output
- **Ruleset Generator** - Export selected sniffs as a ready-to-use `ruleset.xml`
- **Shareable URLs** - Share your configurations with others

## Requirements

- PHP 8.2+
- Node.js 18+
- Composer

## Installation

```bash
# Clone repository
git clone https://github.com/contributte/codestyler.git
cd codestyler

# Install dependencies
make install
make setup

# Build assets
make build
```

## Development

```bash
# Run development server (PHP + Vite)
make dev

# Or run separately:
make server   # PHP server on port 8000
make assets   # Vite dev server
```

Open http://localhost:8000 in your browser.

## Commands

| Command | Description |
|---------|-------------|
| `make install` | Install Composer dependencies |
| `make build` | Build frontend assets for production |
| `make dev` | Start development servers |
| `make server` | Start PHP built-in server |
| `make assets` | Start Vite dev server |
| `make tests` | Run test suite |
| `make cs` | Check code style |
| `make csf` | Fix code style |
| `make clean` | Clean temp/cache directories |
| `make sniffs-cache` | Clear sniffs cache |

## Tech Stack

- **Backend**: Nette Framework with Contributte packages
- **Frontend**: Alpine.js, TailwindCSS, CodeMirror 6
- **Build**: Vite
- **Analysis**: PHP_CodeSniffer, Slevomat Coding Standard

## Development

See [how to contribute](https://contributte.org) to this package. This package is currently maintained by these authors.

<a href="https://github.com/f3l1x">
  <img width="80" height="80" src="https://avatars2.githubusercontent.com/u/538058?v=3&s=80">
</a>

-----

Consider to [support](https://contributte.org/partners.html) **contributte** development team.
Also thank you for using this package.
