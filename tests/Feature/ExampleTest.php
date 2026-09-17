<?php

test('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertOk();
});

test('links to example doctors search queries', function (array $query) {
    $this->get('/')->assertSee(route('doctors.index', $query));
})->with([
    'free text' => [['q' => 'Bukalest']],
    'multiple tokens' => [['q' => 'Popesku Bukalest']],
    'scoped filters' => [['speciality' => 'Kardiology', 'county' => 'Cluj']],
    'clinic with pagination' => [['clinic_name' => 'Clinica Brasov Care', 'per_page' => 5, 'page' => 2]],
]);

test('links to the repository readme as documentation', function () {
    $this->get('/')->assertSee('https://github.com/reppair/wonderful-task#readme', false);
});
