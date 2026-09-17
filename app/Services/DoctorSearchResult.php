<?php

namespace App\Services;

use App\Models\Doctor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class DoctorSearchResult
{
    /**
     * @param  LengthAwarePaginator<int, Doctor>  $doctors
     * @param  array<int, FuzzyMatch>  $corrections
     */
    public function __construct(
        public LengthAwarePaginator $doctors,
        public array $corrections,
    ) {}
}
