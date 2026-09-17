<?php

namespace Database\Factories;

use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Doctor>
 */
class DoctorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $city = fake()->city();

        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'clinic_name' => "Clinica {$city} Care",
            'location' => $city,
            'speciality' => fake()->randomElement([
                'Cardiology', 'Dermatology', 'ENT', 'Endocrinology', 'Family Medicine',
                'Gastroenterology', 'Infectious Diseases', 'Internal Medicine', 'Nephrology',
                'Neurology', 'Obstetrics and Gynecology', 'Oncology', 'Ophthalmology',
                'Orthopedics', 'Pediatrics', 'Psychiatry', 'Pulmonology', 'Radiology',
                'Rheumatology', 'Urology',
            ]),
            'address' => fake()->streetAddress(),
            'phone' => fake()->unique()->numerify('+40-###-###-###'),
            'email' => fake()->unique()->safeEmail(),
            'postal_code' => fake()->numerify('######'),
            'county' => fake()->randomElement([
                'Alba', 'Arad', 'Arges', 'Bihor', 'Brasov', 'Bucharest', 'Cluj',
                'Constanta', 'Dolj', 'Iasi', 'Mures', 'Prahova', 'Sibiu', 'Timis',
            ]),
            'years_experience' => fake()->numberBetween(1, 40),
            'education' => fake()->randomElement([
                'Carol Davila University of Medicine and Pharmacy',
                'George Emil Palade University of Medicine',
                'Grigore T. Popa University of Medicine and Pharmacy',
                'Iuliu Hatieganu University of Medicine and Pharmacy',
                'Ovidius University of Medicine',
                'Victor Babes University of Medicine and Pharmacy',
            ]),
            'languages' => fake()->randomElements(
                ['English', 'French', 'German', 'Hungarian', 'Italian', 'Romanian', 'Spanish'],
                fake()->numberBetween(1, 3),
            ),
            'availability' => fake()->randomElement([
                'Mon-Fri 08:00-16:00', 'Mon-Fri 09:00-17:00', 'Mon-Fri 10:00-18:00',
                'Mon-Sat 09:00-14:00', 'Tue-Sat 08:30-16:30',
            ]),
            'rating' => fake()->randomFloat(1, 3, 5),
        ];
    }
}
