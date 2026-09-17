<?php

use App\Models\Doctor;
use Database\Seeders\DoctorSeeder;

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

test('seeder imports every doctor from the healthcare json file', function () {
    $this->seed(DoctorSeeder::class);

    expect(Doctor::count())->toBe(7029);

    $doctor = Doctor::where('phone', '+40-279-189-704')->sole();

    expect($doctor)
        ->first_name->toBe('Ionut')
        ->last_name->toBe('Dumitrescu')
        ->clinic_name->toBe('Clinica Cluj-Napoca Care')
        ->speciality->toBe('Psychiatry')
        ->county->toBe('Cluj')
        ->years_experience->toBe(3)
        ->languages->toBe(['Italian'])
        ->rating->toBe(3.2);
});
