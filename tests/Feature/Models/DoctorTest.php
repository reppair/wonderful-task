<?php

use App\Models\Doctor;

test('factory persists a doctor with all columns', function () {
    $doctor = Doctor::factory()->create([
        'first_name' => 'Ionut',
        'last_name' => 'Dumitrescu',
        'languages' => ['Romanian', 'English'],
        'rating' => 4.5,
    ]);

    $this->assertDatabaseHas('doctors', [
        'id' => $doctor->id,
        'first_name' => 'Ionut',
        'last_name' => 'Dumitrescu',
        'email' => $doctor->email,
        'rating' => 4.5,
    ]);

    expect($doctor->fresh())
        ->languages->toBe(['Romanian', 'English'])
        ->rating->toBe(4.5)
        ->years_experience->toBeInt()
        ->full_name->toBe('Ionut Dumitrescu');
});
