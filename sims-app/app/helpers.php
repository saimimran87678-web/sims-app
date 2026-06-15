<?php

if (!function_exists('hasFeature')) {
    function hasFeature(string $feature): bool {
        $record = \App\Services\LicenseStatus::getLicenseRecord();
        if (!$record) {
            return false;
        }
        try {
            $plan = decrypt($record->plan);
        } catch (\Exception $e) {
            return false;
        }
        return in_array($feature, config("plans.{$plan}.features", []));
    }
}

if (!function_exists('canWrite')) {
    function canWrite(): bool {
        return \App\Services\LicenseStatus::canWrite();
    }
}
