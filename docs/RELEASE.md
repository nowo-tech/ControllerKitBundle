# Release process

1. Update [CHANGELOG.md](CHANGELOG.md) (version and date).
2. Run `make release-check`.
3. Commit and tag: `git tag v1.0.0`.
4. Push branch and tags: `git push && git push --tags`.
5. Create the release on GitHub (or use your CI release workflow).
6. Publish to Packagist if the package is registered there.
