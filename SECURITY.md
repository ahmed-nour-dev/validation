# Security Policy

`ahmednour/validation` generates, caches and loads executable PHP (native validators) and
PHP-serialized schema files. We take reports about these code paths, and about any way to
bypass a validation rule, seriously.

## Supported versions

The package is pre-1.0. Security fixes go into the latest minor release only.

| Version | Supported |
| :--- | :--- |
| 0.3.x | Yes |
| < 0.3 | No. Please upgrade to the latest 0.3.x release. |

## Reporting a vulnerability

**Please do not open a public issue, pull request or discussion for a suspected
vulnerability.**

Report it privately through GitHub:

1. Go to the repository's [**Security** tab](https://github.com/ahmed-nour-dev/validation/security).
2. Click **Report a vulnerability**
   ([direct link](https://github.com/ahmed-nour-dev/validation/security/advisories/new)).

The report stays private between you and the maintainers until an advisory is published.

### What to include

- The affected version (or commit) and your PHP and Laravel versions, if relevant.
- The component involved, for example native compilation, `FileSchemaCache`, a specific
  rule, `LaravelRuleParser`, override mode or Octane pooling.
- What an attacker can do, and what they need to control to do it (request data, field
  names, rule parameters, the cache directory, ...).
- Steps to reproduce: a minimal schema or rule set and payload, ideally as a short script or
  PHPUnit test.
- Any suggested fix or mitigation you have in mind.

A rough report is fine. You don't need a polished proof of concept before reporting.

## What to expect

| Step | Target time |
| :--- | :--- |
| Acknowledgement of your report | Within 3 business days |
| Initial assessment (confirmed, needs more info, or not a vulnerability) | Within 10 business days |
| Fix released for a confirmed issue | Depends on severity; we aim for 30 days and keep you updated on progress |

We'll keep you informed while we work on the fix, may ask you to check it, and will agree a
disclosure date with you. Please keep the details private until the fix is released and the
advisory is published.

## How fixes are communicated

- A patch release for the supported version line.
- A [GitHub Security Advisory](https://github.com/ahmed-nour-dev/validation/security/advisories),
  with a CVE requested where appropriate. Advisories also show up in `composer audit` via the
  GitHub Advisory Database.
- A **Security** entry in [CHANGELOG.md](CHANGELOG.md).

Reporters are credited in the advisory unless they ask not to be.

## Scope

In scope, for example:

- Code injection through generated native validators or cached schemas.
- Loading a tampered, unsigned or unverified cache file, or bypassing the HMAC signing
  checks.
- A rule that accepts input it should reject (a validation bypass), or behaves differently
  from Laravel in a way that weakens a check.
- Data leaking between requests or workers through `ValidatorPool` / Octane pooling.

Usually out of scope:

- Problems that need an attacker to already be able to write the cache directory, change
  rule definitions or install custom rule classes. These are trusted inputs; see the
  [security model & trust boundaries](docs/native-compilation.md#security-model--trust-boundaries).
  A way to escalate from untrusted input to one of these is in scope.
- Running without a signing key in standalone mode, or with the cache directory inside the web
  root or world-writable, against the documented guidance.
- Vulnerabilities in dependencies such as `illuminate/validation`. Please report those to the
  upstream project. If this package makes one exploitable, tell us too.

If you're unsure whether something is in scope, report it anyway.
