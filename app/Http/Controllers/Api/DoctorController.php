<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DoctorController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'clinic_name' => ['nullable', 'string', 'max:255'],
            'speciality' => ['nullable', 'string', 'max:255'],
            'county' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $doctors = Doctor::query()
            ->search($validated['q'] ?? null)
            ->when($validated['clinic_name'] ?? null, fn ($query, string $clinicName) => $query->where('clinic_name', $clinicName))
            ->when($validated['speciality'] ?? null, fn ($query, string $speciality) => $query->where('speciality', $speciality))
            ->when($validated['county'] ?? null, fn ($query, string $county) => $query->where('county', $county))
            ->when($validated['location'] ?? null, fn ($query, string $location) => $query->where('location', $location))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return DoctorResource::collection($doctors);
    }
}
