<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Access\Gate;

it('denies abilities that have not been explicitly defined', function (): void {
    $decision = app(Gate::class)->inspect('foundation.undefined-ability');

    expect($decision->denied())->toBeTrue();
});
