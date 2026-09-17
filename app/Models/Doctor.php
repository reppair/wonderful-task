<?php

namespace App\Models;

use Database\Factories\DoctorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $clinic_name
 * @property string $location
 * @property string $speciality
 * @property string $address
 * @property string $phone
 * @property string $email
 * @property string $postal_code
 * @property string $county
 * @property int $years_experience
 * @property string $education
 * @property array<int, string> $languages
 * @property string $availability
 * @property float $rating
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $full_name
 *
 * @method static Builder<static> search(?string $term)
 */
#[Fillable([
    'first_name',
    'last_name',
    'clinic_name',
    'location',
    'speciality',
    'address',
    'phone',
    'email',
    'postal_code',
    'county',
    'years_experience',
    'education',
    'languages',
    'availability',
    'rating',
])]
class Doctor extends Model
{
    /** @use HasFactory<DoctorFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'years_experience' => 'integer',
            'languages' => 'array',
            'rating' => 'float',
        ];
    }

    /**
     * @return Attribute<non-falsy-string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => "{$this->first_name} {$this->last_name}");
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $pattern = "%{$term}%";

        return $query->where(function (Builder $query) use ($pattern): void {
            $query->where('first_name', 'like', $pattern)
                ->orWhere('last_name', 'like', $pattern)
                ->orWhere('clinic_name', 'like', $pattern)
                ->orWhere('location', 'like', $pattern)
                ->orWhere('speciality', 'like', $pattern)
                ->orWhere('county', 'like', $pattern);
        });
    }
}
