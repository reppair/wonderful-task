<?php

namespace App\Http\Resources;

use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Doctor
 */
class DoctorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'clinic_name' => $this->clinic_name,
            'location' => $this->location,
            'speciality' => $this->speciality,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'postal_code' => $this->postal_code,
            'county' => $this->county,
            'years_experience' => $this->years_experience,
            'education' => $this->education,
            'languages' => $this->languages,
            'availability' => $this->availability,
            'rating' => $this->rating,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
