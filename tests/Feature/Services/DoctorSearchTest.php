<?php

use App\Models\Doctor;
use App\Services\DoctorSearch;

test('splits a fuzzy free-text term on any run of whitespace', function (string $term) {
    $match = Doctor::factory()->create(['first_name' => 'Robert', 'location' => 'Bucharest']);
    Doctor::factory()->create(['first_name' => 'Robert', 'location' => 'Cluj-Napoca']);
    Doctor::factory()->create(['first_name' => 'Ana', 'location' => 'Bucharest']);

    $result = app(DoctorSearch::class)->paginate(['q' => $term]);

    expect($result->doctors->items())->toHaveCount(1)
        ->and($result->doctors->items()[0]->id)->toBe($match->id)
        ->and($result->corrections)->toHaveCount(2)
        ->and($result->corrections[0]->matched)->toBe('Robert')
        ->and($result->corrections[1]->matched)->toBe('Bucharest');
})->with([
    'multiple spaces' => ['Dobert   Bukalest'],
    'tab' => ["Dobert\tBukalest"],
    'newline' => ["Dobert\nBukalest"],
    'surrounding whitespace' => ["  Dobert Bukalest \n"],
]);
