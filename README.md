This branch is created by initiative of the Magento squad to discuss the architecture of the new extension.

The *goal* is: **Improving the extension quality with an iterative refactoring**.

## Modules organization

A module is a logical group – that is, a directory containing blocks, controllers, helpers, models – that are
related to a specific business feature. A module encapsulates one feature and has minimal dependencies on other modules.

More info about this: [https://devdocs.magento.com/guides/v2.3/architecture/archi_perspectives/components/modules/mod_intro.html](https://devdocs.magento.com/guides/v2.3/architecture/archi_perspectives/components/modules/mod_intro.html)

- Module name pattern: `Algolia{Product}{Concern}`:

```
./composer.json : at root level, depends of all modulues, and contains development dependencies
./Algolia{Product}{Concern}/composer.json : at module level, has minimal dependencies on other modules
./Algolia{Product}{Concern}/Test/Unit : at module level, contains unit tests
./Algolia{Product}{Concern}/Test/Mftf : at module level, contains integration tests with (or not) other modules
```

## Deployment

On a new version, the script `composer release` should be performed to release the extension on the git/marketplace. Note
that the script `composer release` is in charge to perform environment/misc validations to ensure that a new version can
be released.

The end-goal would add this script to a workflow on circle-ci, that should run on push-to-master.

- `composer release`

## Test suite

The test suite run before on a `pre-commit` hook. As so, it should be fast, reliable, isolated, and without flakiness:

composer.json > extra > hooks.

> It's important to NOT understime the importance of this task. A well designed test suite, will drastically 
reduce the amount of time we spend debugging.

The test suite can also be run manually using:

- `composer test` Runs the whole test suite in `--dry-run` mode.

## Coding Style

Ensures good practices and clean code:

- `composer lint` Runs the linter.
- `composer test:lint` Runs the linter in `--dry-run`.

## Static Analysis

Static analyics focuses on finding errors in your code without actually running it. Contains rules to get us
on the habit of writing robust, safe, and maintainable code.

They are aggressive, but just TypeScript, they point you in the right direction.

- `composer test:types` Runs the type checker.

## TODO

[ ] - Create the `Search` product along side their respective modules, unit tests and integration tests
[ ] - Develop `dev/release.sh`
[ ] - Develop `.circle.ci` without pre-docker images this time, and run tests against: magento version <=> php version <=> lower|high dependencies
[ ] - Consider `https://github.com/bamarni/composer-bin-plugin` for development dependencies. As magento seems stuck in older versions of symfony compoments.
