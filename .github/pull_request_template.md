<!-- Every pull request closes at least one issue. One line per issue: -->
Closes #

## What changed

<!-- For several issues, list the changes under each issue number. -->

## How it was tested

- [ ] `composer lint`, `composer phpcs`, `composer phpstan` and `composer test` pass
- [ ] `npm run lint` passes (and `npm run build` was run and committed after block changes)
- [ ] Tried in WordPress (or covered by `npm run test:integration`)

<!--
Will merging this make a release? Only if the plugin zip changes, that is, a
file .distignore does not exclude (PHP, assets, build/, readme.txt...). Docs,
tests, CI and config changes make no release. The version goes up by a patch;
add the label release:minor or release:major for more. Don't edit the version
or the changelog by hand.
-->
