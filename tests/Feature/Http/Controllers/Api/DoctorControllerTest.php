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

test('corrects a transcribed location typo in the free-text search and reports it in meta', function () {
    $match = Doctor::factory()->create(['location' => 'Bucharest']);
    Doctor::factory()->create(['location' => 'Cluj-Napoca']);

    $this->getJson(route('doctors.index', ['q' => 'Bukalest']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id)
        ->assertJsonPath('meta.fuzzy', [
            ['query' => 'Bukalest', 'matched' => 'Bucharest', 'column' => 'location', 'distance' => 3],
        ]);
});

test('combines corrected free-text tokens with AND', function () {
    $match = Doctor::factory()->create(['first_name' => 'Robert', 'location' => 'Bucharest']);
    Doctor::factory()->create(['first_name' => 'Robert', 'location' => 'Cluj-Napoca']);
    Doctor::factory()->create(['first_name' => 'Ana', 'location' => 'Bucharest']);

    $this->getJson(route('doctors.index', ['q' => 'Dobert Bukalest']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id)
        ->assertJsonPath('meta.fuzzy.0.matched', 'Robert')
        ->assertJsonPath('meta.fuzzy.1.matched', 'Bucharest');
});

test('matches a correctly spelled multi-word free-text search across columns', function () {
    $match = Doctor::factory()->create(['first_name' => 'Robert', 'location' => 'Bucharest']);
    Doctor::factory()->create(['first_name' => 'Robert', 'location' => 'Cluj-Napoca']);

    $this->getJson(route('doctors.index', ['q' => 'Robert Bucharest']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id)
        ->assertJsonPath('meta.fuzzy', []);
});

test('corrects a typo in a multi-word value as a whole phrase', function () {
    $match = Doctor::factory()->create(['location' => 'Baia Mare']);
    Doctor::factory()->create(['location' => 'Satu Mare']);

    $this->getJson(route('doctors.index', ['q' => 'Baya Mare']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id)
        ->assertJsonPath('meta.fuzzy.0.matched', 'Baia Mare');
});

test('corrects a transcribed typo in a scoped filter', function () {
    $match = Doctor::factory()->create(['location' => 'Bucharest', 'speciality' => 'Cardiology']);
    Doctor::factory()->create(['location' => 'Bucharest', 'speciality' => 'Urology']);

    $this->getJson(route('doctors.index', ['location' => 'Bukalest', 'speciality' => 'Kardiology']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id)
        ->assertJsonCount(2, 'meta.fuzzy')
        ->assertJsonFragment(['query' => 'Bukalest', 'matched' => 'Bucharest', 'column' => 'location'])
        ->assertJsonFragment(['query' => 'Kardiology', 'matched' => 'Cardiology', 'column' => 'speciality']);
});

test('returns no results and no correction when nothing is close enough', function () {
    Doctor::factory()->create(['location' => 'Bucharest']);

    $this->getJson(route('doctors.index', ['q' => 'Xyzabc']))
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0)
        ->assertJsonPath('meta.fuzzy', []);
});
