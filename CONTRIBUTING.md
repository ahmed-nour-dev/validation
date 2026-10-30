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


## Attribution
Our contributing guidelines are inspired by plenty of successful open-source projects.
