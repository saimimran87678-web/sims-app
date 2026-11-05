<?php

namespace App\Services;

use App\Models\GradeScale;
use Illuminate\Support\Collection;

class GradeCalculationService
{
    /**
     * Cache scales per session during request lifecycle.
     */
    protected static array $scalesCache = [];

    /**
     * Fetch ordered grade scales for session with caching.
     */
    public static function getScales(?int $sessionId = null): Collection
    {
        $cacheKey = $sessionId ?? 0;
        if (!isset(self::$scalesCache[$cacheKey])) {
            self::$scalesCache[$cacheKey] = GradeScale::getScalesForSession($sessionId);
        }

        return self::$scalesCache[$cacheKey];
    }

    /**
     * Clear memory cache (useful for unit tests).
     */
    public static function clearCache(): void
    {
        self::$scalesCache = [];
    }

    /**
     * Resolve the GradeScale model matching a given percentage.
     */
    public function getScaleForPercentage(float $percentage, ?int $sessionId = null): ?GradeScale
    {
        $scales = self::getScales($sessionId);
        $clampedPct = max(0.0, min(100.0, $percentage));

        foreach ($scales as $scale) {
            $from = (float) $scale->percentage_from;
            $to = (float) $scale->percentage_to;

            if ($clampedPct >= $from && $clampedPct <= $to) {
                return $scale;
            }
        }

        // Fallback to lowest or highest
        if ($clampedPct >= 90.0) {
            return $scales->first();
        }

        return $scales->last();
    }

    /**
     * Calculate granular grade evaluation for a single mark record.
     */
    public function calculateFromMark(?float $obtained, float $maxMarks = 100.0, bool $isAbsent = false, ?int $sessionId = null): array
    {
        if ($isAbsent) {
            return [
                'status' => 'absent',
                'status_label' => 'ABS',
                'obtained' => null,
                'max' => $maxMarks,
                'percentage' => null,
                'grade' => 'ABS',
                'gpa_point' => 0.00,
                'remarks' => 'Absent',
                'color' => 'text-rose-600 font-semibold',
                'is_passing' => false,
            ];
        }

        if (is_null($obtained)) {
            return [
                'status' => 'pending',
                'status_label' => '-',
                'obtained' => null,
                'max' => $maxMarks,
                'percentage' => null,
                'grade' => '-',
                'gpa_point' => null,
                'remarks' => 'Pending Entry',
                'color' => 'text-gray-400 italic',
                'is_passing' => null,
            ];
        }

        $max = max(1.0, (float) $maxMarks);
        $pct = round(((float) $obtained / $max) * 100, 2);
        $scale = $this->getScaleForPercentage($pct, $sessionId);

        return [
            'status' => 'scored',
            'status_label' => (string) $obtained,
            'obtained' => (float) $obtained,
            'max' => $max,
            'percentage' => $pct,
            'grade' => $scale?->grade ?? 'F',
            'gpa_point' => (float) ($scale?->gpa_point ?? 0.00),
            'remarks' => $scale?->remarks ?? '',
            'color' => $scale?->color ?? 'text-gray-600',
            'is_passing' => (bool) ($scale?->is_passing ?? false),
        ];
    }

    /**
     * Calculate aggregate results across a student's subjects.
     */
    public function calculateAggregate(iterable $marks, ?int $sessionId = null): array
    {
        $totalObtained = 0.0;
        $totalMax = 0.0;
        $scoredCount = 0;
        $pendingCount = 0;
        $absentCount = 0;
        $failedCount = 0;
        $gpaPoints = [];

        foreach ($marks as $mark) {
            $obtained = is_array($mark) ? ($mark['marks_obtained'] ?? null) : ($mark->marks_obtained ?? null);
            $max = (float) (is_array($mark) ? ($mark['max_marks'] ?? 100.0) : ($mark->max_marks ?? 100.0));
            $isAbsent = (bool) (is_array($mark) ? ($mark['is_absent'] ?? false) : ($mark->is_absent ?? false));

            $eval = $this->calculateFromMark(
                is_null($obtained) ? null : (float) $obtained,
                $max,
                $isAbsent,
                $sessionId
            );

            $totalMax += $max;

            if ($eval['status'] === 'scored') {
                $totalObtained += (float) $eval['obtained'];
                $scoredCount++;
                $gpaPoints[] = $eval['gpa_point'];

                if (!$eval['is_passing']) {
                    $failedCount++;
                }
            } elseif ($eval['status'] === 'absent') {
                $absentCount++;
                $failedCount++;
                $gpaPoints[] = 0.00;
            } elseif ($eval['status'] === 'pending') {
                $pendingCount++;
            }
        }

        $overallPct = ($totalMax > 0 && $scoredCount > 0)
            ? round(($totalObtained / $totalMax) * 100, 2)
            : 0.0;

        $overallScale = $this->getScaleForPercentage($overallPct, $sessionId);
        $averageGpa = count($gpaPoints) > 0
            ? round(array_sum($gpaPoints) / count($gpaPoints), 2)
            : 0.00;

        $resultStatus = 'Incomplete';
        if ($pendingCount === 0) {
            $resultStatus = ($failedCount === 0 && $absentCount === 0) ? 'Pass' : 'Fail';
        }

        return [
            'total_obtained' => $totalObtained,
            'total_max' => $totalMax,
            'percentage' => $overallPct,
            'grade' => $overallScale?->grade ?? 'F',
            'gpa' => $averageGpa,
            'remarks' => $overallScale?->remarks ?? '',
            'color' => $overallScale?->color ?? 'text-gray-600',
            'scored_count' => $scoredCount,
            'pending_count' => $pendingCount,
            'absent_count' => $absentCount,
            'failed_count' => $failedCount,
            'result_status' => $resultStatus,
        ];
    }
}
