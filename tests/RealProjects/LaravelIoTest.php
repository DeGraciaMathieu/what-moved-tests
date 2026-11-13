<?php

use Tests\Support\LaravelIo;
use Tests\Support\WhatMoved;

/*
 * laravel.io as its maintainers wrote it, without any config for what-moved: the way a project first meets the tool.
 * The figures were checked once against the code of each commit, then frozen here.
 */

// The pull request #1375, "Add GitHub account connection and disconnection functionality".
const PULL_REQUEST_1375_BASE = 'd2478354^';
const PULL_REQUEST_1375_HEAD = 'd2478354';

it('maps every class of the application', function () {
    $analysis = WhatMoved::analyse(LaravelIo::at(PULL_REQUEST_1375_HEAD), config: null);

    expect($analysis->classes())->toHaveCount(200)
        ->and(array_unique(array_values($analysis->contexts())))->toContain('Actions', 'Http', 'Jobs', 'Livewire', 'Models', 'Policies')
        ->and($analysis->dependenciesOf('App\Actions\ConnectGitHubAccount'))->toEqualCanonicalizing(['App\Jobs\UpdateUserIdenticonStatus', 'App\Models\User']);
})->group('real');

it('shows what pull request #1375 moved', function () {
    $pullRequest = WhatMoved::pullRequest(LaravelIo::at(PULL_REQUEST_1375_BASE), LaravelIo::at(PULL_REQUEST_1375_HEAD), config: null);

    expect($pullRequest->summary)
        ->toContain('**3** classes added, **2** changed, **0** removed, out of 200 · **6** dependencies added, **0** removed.')
        ->toContain('Contexts touched: `Actions` (2, new context), `Http` (2), `Models` (1).')
        ->and($pullRequest->newContexts())->toBe(['Actions'])
        ->and(array_keys(array_filter($pullRequest->arrows(), fn (array $arrow): bool => $arrow['added'] > 0)))
        ->toEqualCanonicalizing(['Actions → Jobs', 'Actions → Models', 'Http → Actions']);
})->group('real');
