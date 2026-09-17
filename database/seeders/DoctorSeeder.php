<?php

namespace Database\Seeders;

use App\Models\Doctor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DoctorSeeder extends Seeder
{
    /**
     * Seed the doctors table from the healthcare data export.
     */
    public function run(): void
    {
        collect(File::json(database_path('helthcate_data.json')))
            ->chunk(500)
            ->each(fn ($chunk) => Doctor::fillAndInsert($chunk->all()));
    }
}
