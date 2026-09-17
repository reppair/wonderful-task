<?php

namespace App\Services;

final readonly class DoctorIngestSummary
{
    public function __construct(
        public int $imported,
        public int $skipped,
    ) {}
}
