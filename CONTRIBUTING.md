# Contributing to ahmednour/validation

First off, thank you for considering contributing to ahmednour/validation! It's people like you who make the open-source community such an amazing place.

## Code of Conduct

By participating in this project, you are expected to uphold our Code of Conduct. (Currently following standard professional ethics).

## How Can I Contribute?

### Reporting Bugs
- **Security vulnerabilities:** don't open a public issue. Report them privately as described in [SECURITY.md](SECURITY.md).
- Use a clear and descriptive title.
- Describe the exact steps which reproduce the problem.
- Provide a code snippet that demonstrates the issue.
- Explain which behavior you expected to see instead and why.

### Suggesting Enhancements
- Use a clear and descriptive title.
- Provide a step-by-step description of the suggested enhancement.
- Explain why this enhancement would be useful to most ahmednour/validation users.

### Pull Requests
- Fill in the pull request template.
- Ensure the test suite passes (`./vendor/bin/phpunit`).
- Ensure PHPStan passes at level 8 (`./vendor/bin/phpstan analyse`).
- Maintain existing coding standards (PSR-12).
- Document any change in behavior.
- Add tests for new features or bug fixes (aim for high coverage).
- Follow the [public API policy](docs/public-api.md) (see below).

## Public API and Deprecations

[docs/public-api.md](docs/public-api.md) defines what is public and how it may change. In short:

- **New classes** are internal unless you mean to support them. Tag them `@internal` in the class
  docblock, or list them in `docs/public-api.md` and in `PublicSurfaceTest::PUBLIC_API`.
  `PublicSurfaceTest` fails for a class that is neither.
- **Breaking a public API** (removing or renaming something, changing a return shape, a config
  default or validation results) needs a minor release before 1.0 and a major release after.
  Say so in the CHANGELOG under **Changed** or **Removed**, with an upgrade note.
- **Deprecating** something: add `@deprecated` with the version, the replacement and the earliest
  removal version, list it under **Deprecated** in the CHANGELOG and in the "Currently deprecated"
  table of `docs/public-api.md`. Keep it working until it is removed.
- If you have to change a test in `tests/Unit/Contract/`, the change is breaking: treat it as one.


## Development Setup

1. Clone the repository.
2. Install dependencies: `composer install`.
3. Run tests: `./vendor/bin/phpunit`.
4. Run static analysis: `./vendor/bin/phpstan analyse`.


## GitHub Actions Pinning

Every action in `.github/workflows/` is pinned to a full 40-character commit SHA, with the release tag in a
comment beside it:

```yaml
uses: actions/checkout@11d5960a326750d5838078e36cf38b85af677262 # v4.4.0
```

Tags can be moved; commit SHAs cannot. Pinning means CI only runs action code that has been reviewed.

**Updating pins**

- Dependabot (`.github/dependabot.yml`) checks weekly and opens a PR that updates both the SHA and the version
  comment. Review the upstream release notes and diff before merging.
- To update a pin by hand, resolve the tag to its commit and confirm it matches the release on GitHub:

  ```bash
  gh api repos/actions/checkout/commits/v4.4.0 --jq .sha
  ```

  Replace the SHA and update the version comment. Never pin a SHA from a fork or an unreleased branch.
- When an upstream security advisory is published for an action we use, update that pin right away rather than
  waiting for the weekly run.
- New workflow steps must follow the same format. Do not reference an action by tag or branch alone.


## Dependency Security Auditing

`.github/workflows/security-audit.yml` resolves the dependency tree for every supported PHP x Laravel
combination (the same matrix as `ci.yml`) and runs `composer audit` against it. It runs:

- on every push and pull request to `main`,
- daily on `main`, so advisories published while no PR is open are still caught,
- on demand from the **Actions** tab (**Security audit** → **Run workflow**).

It is a separate workflow from CI, so a failed **Security audit** check means a dependency has a published
advisory. It does not mean the tests failed. Abandoned packages are reported as warnings and don't fail the job.

GitHub emails scheduled-run failures to the person who last changed the workflow's `schedule`. GitHub also
disables scheduled workflows after 60 days without repository activity; re-enable it from the **Actions** tab.

To run the same check locally:

```bash
composer update
composer audit
```

**Dependency update PRs**

Dependabot (`.github/dependabot.yml`) checks Composer dependencies weekly. This is a library with no committed
`composer.lock`, so it only opens a PR when a new release falls outside a `composer.json` constraint. These PRs
run the full CI matrix and the security audit like any other PR. Don't merge one until both pass.

**When the audit fails**

1. Open the failing job and note the advisory ID (CVE or GHSA), the package and the affected versions.
2. If a fixed release exists inside our constraints, nothing in this repository needs to change: users get it on
   their next `composer update`, and so does CI. Re-run the workflow once the fix is published to Packagist.
3. If the fix is only available outside our constraints, raise the lower bound in `composer.json` (for example
   `^11.0` → `^11.44.1`) in a normal PR, and add a **Security** entry to `CHANGELOG.md`.

**When there is no fix yet**

1. Work out whether this package is affected. Is the dependency in `require` (shipped to users) or only in
   `require-dev` (CI and contributors)? Does this package use the vulnerable code path?
2. If this package makes the vulnerability exploitable, treat it as a vulnerability in this package and follow
   [SECURITY.md](SECURITY.md). Don't discuss it in a public issue.
3. Otherwise, open a tracking issue that links the advisory and explains why the package is or isn't affected.
   If users can mitigate it (a config option, avoiding a rule, pinning another package), say so there.
4. Unblock CI by ignoring that one advisory in `composer.json`, with the reason and the tracking issue:

   ```json
   "config": {
       "audit": {
           "ignore": {
               "GHSA-xxxx-xxxx-xxxx": "No fix released yet; only affects X, which we don't use. See #123."
           }
       }
   }
   ```

   Ignore by advisory ID, one entry per advisory. Never ignore a whole package or turn the audit off.
5. Once a fix ships, remove the `ignore` entry, raise the constraint if needed, and close the issue.


## Attribution
Our contributing guidelines are inspired by plenty of successful open-source projects.
