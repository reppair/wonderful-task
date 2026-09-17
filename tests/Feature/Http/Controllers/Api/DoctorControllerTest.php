<?php

use App\Models\Doctor;

test('searches doctors by free text combined with an exact county filter', function () {
    $match = Doctor::factory()->create([
        'first_name' => 'Ana',
        'last_name' => 'Popescu',
        'speciality' => 'Cardiology',
        'county' => 'Cluj',
    ]);
    Doctor::factory()->create(['speciality' => 'Cardiology', 'county' => 'Timis']);
    Doctor::factory()->create(['speciality' => 'Dermatology', 'county' => 'Cluj']);

    $this->getJson(route('doctors.index', ['q' => 'cardio', 'county' => 'Cluj']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id)
        ->assertJsonPath('data.0.full_name', 'Ana Popescu')
        ->assertJsonPath('meta.total', 1);
});

test('returns 422 for an invalid per_page value', function () {
    $this->getJson(route('doctors.index', ['per_page' => 0]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('per_page');
});

test('filters by clinic name without matching other columns', function () {
    $match = Doctor::factory()->create([
        'clinic_name' => 'Clinica Brasov Care',
        'location' => 'Sibiu',
        'county' => 'Sibiu',
    ]);
    Doctor::factory()->create([
        'clinic_name' => 'Clinica Sibiu Care',
        'location' => 'Brasov',
        'county' => 'Brasov',
    ]);

    $this->getJson(route('doctors.index', ['clinic_name' => 'Clinica Brasov Care']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id)
        ->assertJsonPath('meta.total', 1);
});
