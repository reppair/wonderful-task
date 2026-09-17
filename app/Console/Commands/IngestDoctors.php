<?php

namespace App\Console\Commands;

use App\Services\DoctorIngest;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('doctors:ingest')]
#[Description('Fetch the doctors dataset and replace the doctors table with it')]
class IngestDoctors extends Command
{
    public function handle(DoctorIngest $ingest): int
    {
        try {
            $summary = $ingest->run();
        } catch (Throwable $e) {
            Log::error('Doctor ingest failed; existing doctors left untouched.', ['exception' => $e]);
            $this->components->error("Doctor ingest failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        $message = "Doctor ingest complete: imported {$summary->imported}, skipped {$summary->skipped}.";

        Log::info($message, ['imported' => $summary->imported, 'skipped' => $summary->skipped]);
        $this->components->info($message);

        return self::SUCCESS;
    }
}
