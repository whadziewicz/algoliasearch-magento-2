This branch is created by an initiative of the Magento squad to discuss the architecture of the new extension.

The *goal* is: **Improving the extension quality with an iterative refactoring**.

## Modules organization

A module is a logical group – that is, a directory containing blocks, controllers, helpers, models – that are
related to a specific business feature. A module encapsulates one feature and has minimal dependencies on other modules.

More info about this: [devdocs.magento.com/guides/v2.3/architecture/archi_perspectives/components/modules/mod_intro.html](https://devdocs.magento.com/guides/v2.3/architecture/archi_perspectives/components/modules/mod_intro.html)

- Module name pattern: `Algolia{Product}{Concern}`. Example: AlgoliaSearchAdmin, AlgoliaAnalyticsAdmin.

Example: [github.com/algolia/algoliasearch-magento-2/tree/release/v2.0.0/AlgoliaSearchAdmin](https://github.com/algolia/algoliasearch-magento-2/tree/release/v2.0.0/AlgoliaSearchAdmin)

```
./composer.json : at root level (it's not a module, is a metapackage), depends of all modules, and contains DEV dependencies
./Algolia{Product}{Concern}/composer.json : at module level, has minimal dependencies on other modules
```

Here is an example:

```
AlgoliaAnalytics: contains features related to analytics
AlgoliaSearchAdmin: contains admin code related to search
AlgoliaSearchCore: contains indexation part, or similar
AlgoliaSearchFrontend: contains front-end assets related to search
...
```

Note that, we can have everthing in the same repository as no git history is needed on the market place.

## Test suite

Here is where tests should be localled and they goal:
```
./Algolia{Product}{Concern}/Test/Unit : at module level, contains UNIT tests
./Algolia{Product}{Concern}/Test/Mftf : at module level, contains INTEGRATION tests with (or not) other modules
```

The test suite will run as  `pre-commit` hook. As so, it should be fast, reliable, isolated, and without flakiness:

composer.json > extra > hooks.

> It's important to NOT underestimate the importance of this task. A well-designed test
 suite, will drastically reduce the amount of time we spend debugging and on support.

The test suite can also be run manually using:

- `composer test` Runs the whole test suite (except the integration testing) in `--dry-run` mode.

## Continous integration

As discussed on the point `release`, eventually the CI will be responsible to release new versions of the extension. But before
that is important to set up a test suite on the CI that tests the current extension against different scenarios, here are some:

```
- magento 101, php 7.0
- magento 101, php 7.1
- magento 101, php 7.2
- magento 102, php 7.0
...
```

## Coding Style

Ensures well designed, robust, and clean code:

- `composer lint` Runs the linter
- `composer test:lint` Runs the linter in `--dry-run`

It's fine to change coding style rules during the development.

## Static Analysis

Static analysis focuses on finding errors in your code without actually running it. Contains rules to get us
on the habit of writing robust, safe, and maintainable code.

They are aggressive, but just TypeScript, they point you in the right direction.

- `composer test:types` Runs the type checker.

## Deployment

On a new version, the script `composer release` should be performed to release the extension on the git/marketplace. Note
that the script `composer release` is in charge to perform environment/misc validations to ensure that a new version can
be released.

The end-goal would add this script to a workflow on circle-ci, that should run on push-to-master.

- `composer release`

## Next step

Create a small module ( POC ) and discuss, as a team, coding conventions. Where to place domain logic? Where to places jobs that
may be queued? Where to place abstractions? They may go to an `AlgoliaApi` module itself. Where queries should be placed? Should
code be coupled to Magento? Make understandable looking at the source where code of modules where code belongs.

The POC should be delivered alongside a document `CONVENTIONS.md` where those conventions should be written.

Notes:
- Scripts as `dev/release.sh`, `.circle.ci`, are just examples in this skeleton, as so, they need to be developed
- Mftf integration framework need to be investigated and tested

**Result expected**: A POC alongside with a doc with conventions for the next modules.
