<?php

namespace App\Services;

use App\Models\Doctor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Resolves typo'd search input (e.g. STT transcriptions like "Bukalest") to the closest
 * canonical value stored in a doctor column, using a length-relative Levenshtein threshold.
 */
final class FuzzyMatcher
{
    public const array COLUMNS = ['first_name', 'last_name', 'clinic_name', 'location', 'county', 'speciality'];

    private const string CACHE_KEY = 'doctors.fuzzy-candidates';

    private const int EXACT_ONLY_MAX_LENGTH = 3;

    /**
     * Whether any candidate in any column contains the term, mirroring a LIKE %term% query.
     */
    public function contains(string $term): bool
    {
        $normalized = self::normalize($term);

        foreach (self::COLUMNS as $column) {
            foreach ($this->candidates($column) as $candidate) {
                if (str_contains(self::normalize($candidate), $normalized)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function match(string $column, string $input): ?FuzzyMatch
    {
        return $this->best($input, [$column]);
    }

    public function matchAny(string $input): ?FuzzyMatch
    {
        return $this->best($input, self::COLUMNS);
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function normalize(string $value): string
    {
        return Str::of($value)->ascii()->lower()->squish()->toString();
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function best(string $input, array $columns): ?FuzzyMatch
    {
        $normalizedInput = self::normalize($input);

        if ($normalizedInput === '') {
            return null;
        }

        $scored = [];

        foreach ($columns as $column) {
            foreach ($this->candidates($column) as $candidate) {
                $normalizedCandidate = self::normalize($candidate);
                $distance = levenshtein($normalizedInput, $normalizedCandidate);

                if ($distance > $this->threshold($normalizedInput, $normalizedCandidate)) {
                    continue;
                }

                similar_text($normalizedInput, $normalizedCandidate, $similarity);

                $scored[] = [
                    'match' => new FuzzyMatch($input, $candidate, $column, $distance),
                    'distance' => $distance,
                    'similarity' => $similarity,
                ];
            }
        }

        if ($scored === []) {
            return null;
        }

        usort($scored, fn (array $a, array $b): int => [$a['distance'], $b['similarity']] <=> [$b['distance'], $a['similarity']]);

        if ($this->isAmbiguous($scored)) {
            return null;
        }

        return $scored[0]['match'];
    }

    /**
     * @param  array<int, array{match: FuzzyMatch, distance: int, similarity: float}>  $scored
     */
    private function isAmbiguous(array $scored): bool
    {
        if (count($scored) < 2) {
            return false;
        }

        [$first, $second] = $scored;

        return $first['distance'] === $second['distance']
            && $first['similarity'] === $second['similarity']
            && $first['match']->matched !== $second['match']->matched;
    }

    private function threshold(string $input, string $candidate): int
    {
        if (strlen($input) <= self::EXACT_ONLY_MAX_LENGTH) {
            return 0;
        }

        return intdiv(max(strlen($input), strlen($candidate)), 3);
    }

    /**
     * @return array<int, string>
     */
    private function candidates(string $column): array
    {
        return $this->allCandidates()[$column] ?? [];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function allCandidates(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn (): array => collect(self::COLUMNS)
            ->mapWithKeys(fn (string $column): array => [
                $column => Doctor::query()->distinct()->orderBy($column)->pluck($column)->all(),
            ])
            ->all());
    }
}
