<?php

namespace App\Services;

use App\Models\Doctor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class DoctorSearch
{
    private const array SCOPED_FILTERS = ['clinic_name', 'speciality', 'county', 'location'];

    private const int DEFAULT_PER_PAGE = 15;

    /** @var array<int, FuzzyMatch> */
    private array $corrections = [];

    public function __construct(private readonly FuzzyMatcher $matcher) {}

    /**
     * @param  array{q?: ?string, clinic_name?: ?string, speciality?: ?string, county?: ?string, location?: ?string, per_page?: ?int}  $params
     */
    public function paginate(array $params): DoctorSearchResult
    {
        $this->corrections = [];

        $base = $this->applyScopedFilters(Doctor::query(), $params);
        $term = $params['q'] ?? null;
        $perPage = $params['per_page'] ?? self::DEFAULT_PER_PAGE;

        $doctors = $this->run((clone $base)->search($term), $perPage);

        if (blank($term) || $doctors->total() > 0) {
            return new DoctorSearchResult($doctors, $this->corrections);
        }

        $scopedCorrections = $this->corrections;
        $fuzzyQuery = $this->applyFuzzyTerm(clone $base, $term);

        if ($fuzzyQuery === null) {
            return new DoctorSearchResult($doctors, $scopedCorrections);
        }

        return new DoctorSearchResult($this->run($fuzzyQuery, $perPage), $this->corrections);
    }

    /**
     * @param  Builder<Doctor>  $query
     * @param  array<string, mixed>  $params
     * @return Builder<Doctor>
     */
    private function applyScopedFilters(Builder $query, array $params): Builder
    {
        foreach (self::SCOPED_FILTERS as $column) {
            $value = $params[$column] ?? null;

            if (blank($value)) {
                continue;
            }

            $query->where($column, $this->resolveFilter($column, $value));
        }

        return $query;
    }

    private function resolveFilter(string $column, string $value): string
    {
        $match = $this->matcher->match($column, $value);

        if ($match === null) {
            return $value;
        }

        $this->recordCorrection($match);

        return $match->matched;
    }

    /**
     * Rebuilds the free-text search as one AND-ed condition per token, correcting the whole
     * phrase or the individual tokens that no column contains. Returns null when a token
     * cannot be resolved at all, so the caller keeps the original (empty) result and drops
     * the partial corrections recorded along the way.
     *
     * @param  Builder<Doctor>  $query
     * @return Builder<Doctor>|null
     */
    private function applyFuzzyTerm(Builder $query, string $term): ?Builder
    {
        $tokens = Str::of($term)->squish()->explode(' ')->filter()->values()->all();

        $wholePhrase = count($tokens) > 1 ? $this->matcher->matchAny($term) : null;

        if ($wholePhrase !== null) {
            $this->recordCorrection($wholePhrase);

            return $query->where($wholePhrase->column, $wholePhrase->matched);
        }

        foreach ($tokens as $token) {
            if ($this->matcher->contains($token)) {
                $query->search(FuzzyMatcher::normalize($token));

                continue;
            }

            $match = $this->matcher->matchAny($token);

            if ($match === null) {
                return null;
            }

            $this->recordCorrection($match);
            $query->where($match->column, $match->matched);
        }

        return $query;
    }

    private function recordCorrection(FuzzyMatch $match): void
    {
        if ($match->distance === 0) {
            return;
        }

        $this->corrections[] = $match;
    }

    /**
     * @param  Builder<Doctor>  $query
     * @return LengthAwarePaginator<int, Doctor>
     */
    private function run(Builder $query, int $perPage): LengthAwarePaginator
    {
        return $query
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
