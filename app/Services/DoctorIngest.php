<?php

namespace App\Services;

use App\Models\Doctor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * Replaces the doctors table with a freshly fetched dataset. Any failure before the
 * transactional swap leaves the existing rows untouched.
 */
final class DoctorIngest
{
    private const int CHUNK_SIZE = 500;

    public function __construct(private readonly FuzzyMatcher $matcher) {}

    public function run(): DoctorIngestSummary
    {
        [$rows, $skipped] = $this->validRows($this->fetch());

        if ($rows === []) {
            throw new RuntimeException('The doctors source contained no valid rows.');
        }

        DB::transaction(function () use ($rows): void {
            Doctor::query()->delete();

            foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
                Doctor::fillAndInsert($chunk);
            }
        });

        $this->matcher->forget();

        return new DoctorIngestSummary(count($rows), $skipped);
    }

    /**
     * @return array<int, mixed>
     */
    private function fetch(): array
    {
        $url = config('doctors.source.url');

        $rows = filled($url)
            ? Http::acceptJson()->timeout(config('doctors.source.timeout'))->get($url)->throw()->json()
            : File::json(config('doctors.source.path'), JSON_THROW_ON_ERROR);

        if (! is_array($rows) || ! array_is_list($rows)) {
            throw new RuntimeException('The doctors source did not return a JSON list.');
        }

        return $rows;
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    private function validRows(array $rows): array
    {
        $valid = [];
        $skipped = 0;

        foreach ($rows as $index => $row) {
            $validator = Validator::make(is_array($row) ? $row : [], self::rules());

            if ($validator->fails()) {
                $skipped++;
                Log::warning('Skipping malformed doctor row.', ['index' => $index, 'errors' => $validator->errors()->all()]);

                continue;
            }

            $valid[] = $validator->validated();
        }

        return [$valid, $skipped];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private static function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'clinic_name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'speciality' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'postal_code' => ['required', 'string', 'max:255'],
            'county' => ['required', 'string', 'max:255'],
            'years_experience' => ['required', 'integer', 'min:0'],
            'education' => ['required', 'string', 'max:255'],
            'languages' => ['required', 'array'],
            'languages.*' => ['required', 'string', 'max:255'],
            'availability' => ['required', 'string', 'max:255'],
            'rating' => ['required', 'numeric', 'between:0,5'],
        ];
    }
}
