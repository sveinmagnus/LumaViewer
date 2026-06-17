# Contributing

Thanks for your interest in improving Luma-viewer!

## Getting set up

```bash
composer install        # PHP dependencies + QA tooling
npm install             # block build tooling
npm run build           # compile the blocks into build/
```

A local test site without a full stack:

```bash
npx @wp-playground/cli@latest server --auto-mount --blueprint=./blueprint.json
```

For MemberPress / Elementor testing, use `npx wp-env start` (Docker) and install
those plugins manually.

## Quality gates (run before opening a PR)

```bash
composer run phpcs      # WordPress Coding Standards
composer run phpstan    # static analysis (level 5)
composer run test       # PHPUnit (unit tests, no WordPress required)
npm run build           # blocks must build cleanly
```

CI runs all of the above on every push and pull request. Auto-fix style with
`vendor/bin/phpcbf`.

## Conventions

- PHP 7.4+; follow WordPress Coding Standards. All Luma/remote data must be
  escaped on output; sanitize all input; nonces + capability checks on every
  admin/state-changing action.
- Luma API field access stays isolated in `src/Model/Event.php` and
  `src/Model/Location.php`.
- All output surfaces (shortcode, blocks, Elementor) funnel through
  `src/View/Renderer.php` so markup never diverges.
- Add a unit test for new logic where it can be tested without WordPress
  (see `tests/Unit/`).

## Releasing

Tag a version (`vX.Y.Z`) and push the tag; the release workflow builds a clean
`luma-viewer.zip` and attaches it to the GitHub release. Update `Stable tag`
in `readme.txt`, the plugin header `Version`, and `CHANGELOG.md` first.
