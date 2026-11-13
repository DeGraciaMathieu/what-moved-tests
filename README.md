# what-moved tests

Tests of the analysis of [what-moved](https://github.com/DeGraciaMathieu/what-moved). They run its scripts from the outside, as the action does, on real PHP code, and check what a reviewer is shown: the classes and dependencies found, the summary of the comment, the contexts and arrows of the map. They never look at how the scripts work inside.

- `tests/Analysis`: what the analysis understands of the code, written in each test.
- `tests/PullRequests`: pull requests on a small shop written for the tests, in `tests/Fixtures/shop`.
- `tests/RealProjects`: [laravel.io](https://github.com/laravelio/laravel.io) at fixed commits, cloned into `.cache` on the first run.

## Running them

The tests look for what-moved next to this repository, with its dependencies installed, or where `WHAT_MOVED_PATH` points.

```bash
composer install
composer test               # every test
vendor/bin/pest --exclude-group=real   # without laravel.io, offline
```

The CI runs them against the main branch of what-moved on every push here, every night, and on demand: what-moved holds no workflow, so that it stays publishable on the GitHub Marketplace.
