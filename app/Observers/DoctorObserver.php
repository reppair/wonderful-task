<?php

namespace App\Observers;

use App\Services\FuzzyMatcher;

class DoctorObserver
{
    public function __construct(private readonly FuzzyMatcher $matcher) {}

    public function saved(): void
    {
        $this->matcher->forget();
    }

    public function deleted(): void
    {
        $this->matcher->forget();
    }
}
