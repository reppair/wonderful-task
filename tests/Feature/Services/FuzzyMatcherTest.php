<?php

use App\Models\Doctor;
use App\Services\FuzzyMatcher;
use Database\Seeders\DoctorSeeder;

beforeEach(function () {
    $this->matcher = app(FuzzyMatcher::class);
});

test('resolves STT typos to the closest canonical value', function (string $column, string $input, string $expected, int $distance) {
    Doctor::factory()->create(['first_name' => 'Robert', 'location' => 'Bucharest']);
    Doctor::factory()->create(['first_name' => 'Ana', 'location' => 'Cluj-Napoca']);

    expect($this->matcher->match($column, $input))
        ->matched->toBe($expected)
        ->column->toBe($column)
        ->distance->toBe($distance)
        ->query->toBe($input);
})->with([
    'location Bukalest' => ['location', 'Bukalest', 'Bucharest', 3],
    'first name Dobert' => ['first_name', 'Dobert', 'Robert', 1],
    'first name Doberd' => ['first_name', 'Doberd', 'Robert', 2],
    'diacritics and casing' => ['location', 'bucharést', 'Bucharest', 0],
]);

test('returns null when no candidate is within the length-relative threshold', function () {
    Doctor::factory()->create(['first_name' => 'Robert', 'location' => 'Bucharest']);

    expect($this->matcher->match('location', 'Xyzabc'))->toBeNull()
        ->and($this->matcher->match('first_name', 'Rob'))->toBeNull()
        ->and($this->matcher->match('location', 'Bukalest'))->not->toBeNull();
});

test('returns null when two candidates tie on distance and similarity', function () {
    Doctor::factory()->create(['first_name' => 'Marta']);
    Doctor::factory()->create(['first_name' => 'Marca']);

    expect($this->matcher->match('first_name', 'Marka'))->toBeNull();
});

test('matches across every fuzzy column and reports which one hit', function () {
    Doctor::factory()->create(['speciality' => 'Cardiology', 'county' => 'Prahova']);

    expect($this->matcher->matchAny('Kardiology'))
        ->matched->toBe('Cardiology')
        ->column->toBe('speciality')
        ->and($this->matcher->matchAny('Prahoва'))->matched->toBe('Prahova');
});

test('refreshes cached candidates when a doctor is saved', function () {
    Doctor::factory()->create(['location' => 'Sibiu']);

    expect($this->matcher->match('location', 'Bukalest'))->toBeNull();

    Doctor::factory()->create(['location' => 'Bucharest']);

    expect($this->matcher->match('location', 'Bukalest'))->matched->toBe('Bucharest');
});

test('refreshes cached candidates when the seeder runs', function () {
    expect($this->matcher->candidates('location'))->toBe([]);

    $this->seed(DoctorSeeder::class);

    expect($this->matcher->match('location', 'Bukalest'))->matched->toBe('Bucharest');
});
