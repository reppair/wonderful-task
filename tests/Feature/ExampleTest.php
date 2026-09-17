<?php

test('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertOk();
});

test('links to an example doctors search query', function () {
    $this->get('/')->assertSee(route('doctors.index', ['q' => 'Bukalest']), false);
});
