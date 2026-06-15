<?php

namespace App\Traits;

use App\Services\LicenseStatus;
use Illuminate\Support\Facades\Log;

trait ChecksFeatures
{
    /**
     * Check if a specific feature is enabled in the current school's subscription plan.
     *
     * @param string $feature
     * @return bool
     */
    public function hasFeature(string $feature): bool
    {
        $record = LicenseStatus::getLicenseRecord();
        if (!$record) {
            return false;
        }

        try {
            $plan = decrypt($record->plan);
        } catch (\Exception $e) {
            Log::error('Failed to decrypt plan in hasFeature check: ' . $e->getMessage());
            return false;
        }

        $allowedFeatures = config("plans.{$plan}.features", []);
        return in_array($feature, $allowedFeatures);
    }

    /**
     * Check if the school has reached its resource limits for the given model.
     *
     * @param string $resourceType 'students' | 'teachers' | 'classes'
     * @param int $currentCount
     * @return bool Returns true if the count is within limits, false if limit is reached
     */
    public function isWithinPlanLimit(string $resourceType, int $currentCount): bool
    {
        $record = LicenseStatus::getLicenseRecord();
        if (!$record) {
            return false;
        }

        try {
            $plan = decrypt($record->plan);
        } catch (\Exception $e) {
            return false;
        }

        $limit = config("plans.{$plan}.limits.{$resourceType}", 0);

        if ($limit === -1) {
            return true; // Unlimited
        }

        return $currentCount < $limit;
    }
}
