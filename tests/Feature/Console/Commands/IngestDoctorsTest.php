<?php

use App\Models\Doctor;
use App\Services\FuzzyMatcher;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

const SOURCE_URL = 'https://data.example.test/doctors.json';

beforeEach(function () {
    config()->set('doctors.source.url', SOURCE_URL);
    Http::preventStrayRequests();
});

it('replaces existing doctors with the rows fetched from the configured url', function () {
    $stale = Doctor::factory()->create(['first_name' => 'Stale']);
    Http::fake([SOURCE_URL => Http::response([
        Doctor::factory()->raw(['first_name' => 'Robert', 'location' => 'Bucharest', 'languages' => ['Romanian']]),
        Doctor::factory()->raw(['first_name' => 'Ana', 'location' => 'Cluj-Napoca']),
    ])]);

    $this->artisan('doctors:ingest')
        ->assertSuccessful()
        ->expectsOutputToContain('imported 2, skipped 0');

    $this->assertDatabaseCount('doctors', 2);
    $this->assertModelMissing($stale);
    expect(Doctor::where('first_name', 'Robert')->sole())
        ->location->toBe('Bucharest')
        ->languages->toBe(['Romanian']);
});

it('skips and counts malformed rows without aborting the import', function () {
    Log::spy();
    Http::fake([SOURCE_URL => Http::response([
        Doctor::factory()->raw(['first_name' => 'Robert']),
        ['first_name' => 'Missing', 'rating' => 'not-a-number'],
        'not-an-object',
    ])]);

    $this->artisan('doctors:ingest')
        ->assertSuccessful()
        ->expectsOutputToContain('imported 1, skipped 2');

    $this->assertDatabaseCount('doctors', 1);
    $this->assertDatabaseHas('doctors', ['first_name' => 'Robert']);
    Log::shouldHaveReceived('warning')->twice();
});

it('leaves existing doctors untouched when the fetch fails', function () {
    Log::spy();
    $existing = Doctor::factory()->create();
    Http::fake([SOURCE_URL => Http::response(null, 500)]);

    $this->artisan('doctors:ingest')->assertFailed();

    $this->assertDatabaseCount('doctors', 1);
    $this->assertModelExists($existing);
    Log::shouldHaveReceived('error')->once();
});

it('leaves existing doctors untouched when the source has no valid rows', function (mixed $body) {
    $existing = Doctor::factory()->create();
    Http::fake([SOURCE_URL => Http::response($body)]);

    $this->artisan('doctors:ingest')->assertFailed();

    $this->assertDatabaseCount('doctors', 1);
    $this->assertModelExists($existing);
})->with([
    'empty list' => [[]],
    'only malformed rows' => [[['first_name' => 'Broken']]],
    'not a list' => [['data' => []]],
]);

it('busts the fuzzy candidate cache after a successful refresh', function () {
    $matcher = app(FuzzyMatcher::class);
    Doctor::factory()->create(['location' => 'Sibiu']);
    expect($matcher->match('location', 'Bukalest'))->toBeNull();
    Http::fake([SOURCE_URL => Http::response([Doctor::factory()->raw(['location' => 'Bucharest'])])]);

    $this->artisan('doctors:ingest')->assertSuccessful();

    expect($matcher->match('location', 'Bukalest'))->matched->toBe('Bucharest');
});

it('falls back to the bundled json file when no source url is configured', function () {
    config()->set('doctors.source.url', null);

    $this->artisan('doctors:ingest')
        ->assertSuccessful()
        ->expectsOutputToContain('imported 7029, skipped 0');

    expect(Doctor::where('phone', '+40-279-189-704')->sole())
        ->first_name->toBe('Ionut')
        ->last_name->toBe('Dumitrescu')
        ->clinic_name->toBe('Clinica Cluj-Napoca Care')
        ->speciality->toBe('Psychiatry')
        ->county->toBe('Cluj')
        ->years_experience->toBe(3)
        ->languages->toBe(['Italian'])
        ->rating->toBe(3.2);
});
