<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SystemNotificationService
{
    /**
     * Get aggregated active notifications for the current authenticated user/system.
     *
     * @return array
     */
    public static function getActiveNotifications(): array
    {
        $notifications = [];

        // 1. Check for Software Updates & Patches
        $updateNotice = self::checkUpdateNotification();
        if ($updateNotice) {
            $notifications[] = $updateNotice;
        }

        // 2. Check for Cloud Broadcast Announcement
        $broadcast = LicenseStatus::getBroadcastAnnouncement();
        if (!empty($broadcast)) {
            $notifications[] = [
                'id'           => 'broadcast_' . substr(md5($broadcast), 0, 8),
                'type'         => 'broadcast',
                'title'        => '📢 Adminova Cloud Notice',
                'message'      => $broadcast,
                'action_url'   => null,
                'action_label' => null,
                'badge'        => 'Broadcast',
                'badge_color'  => 'bg-purple-100 text-purple-800 border-purple-200',
                'icon_bg'      => 'bg-gradient-to-br from-indigo-500 to-purple-600',
                'created_at'   => 'Live Announcement',
            ];
        }

        // 3. Check for License Expiry / Status Warnings
        $licenseStatus = LicenseStatus::getStatus();
        $stage = $licenseStatus['stage'] ?? 'ACTIVE';

        if ($stage === LicenseStatus::STAGE_WARNING || $stage === LicenseStatus::STAGE_GRACE) {
            $daysLeft = $licenseStatus['days_left'] ?? ($licenseStatus['days_past'] ?? 0);
            $notifications[] = [
                'id'           => 'license_warning_' . date('Ymd'),
                'type'         => 'license',
                'title'        => '⚠️ License Subscription Notice',
                'message'      => $licenseStatus['message'] ?? "Your subscription requires attention ({$daysLeft} days remaining).",
                'action_url'   => route('license.blocked'),
                'action_label' => 'View License Details',
                'badge'        => 'Urgent',
                'badge_color'  => 'bg-amber-100 text-amber-800 border-amber-200',
                'icon_bg'      => 'bg-gradient-to-br from-amber-500 to-orange-600',
                'created_at'   => 'Subscription Alert',
            ];
        }

        return $notifications;
    }

    /**
     * Check if a newer version or hotfix patch is available from local database setting.
     * ZERO external network blocking during web page render.
     *
     * @return array|null
     */
    public static function checkUpdateNotification(): ?array
    {
        try {
            $offer = json_decode((string) Setting::getGlobal('update_available'), true);
            if (!is_array($offer) || empty($offer['version'])) {
                return null;
            }

            $installedVer = Setting::getGlobal('installed_version') ?: config('app.version', '2.5.2');
            $latestVer = trim($offer['version']);

            if (version_compare($latestVer, $installedVer, '<=')) {
                return null;
            }

            $mandatory = (bool) ($offer['mandatory'] ?? false);
            $notes = $offer['changelog'] ?? ($offer['notes'] ?? 'Includes performance optimizations, bug fixes, and security patches.');

            return [
                'id'           => 'update_' . $latestVer,
                'type'         => 'update',
                'title'        => ($mandatory ? '⚡ Required Update (v' : '🚀 New Version (v') . $latestVer . ')',
                'message'      => $notes,
                'version'      => $latestVer,
                'current'      => $installedVer,
                'action_url'   => route('admin.settings', ['tab' => 'updates']) . '#updates-tab',
                'action_label' => 'Update System Now',
                'badge'        => $mandatory ? 'Required' : "v{$latestVer}",
                'badge_color'  => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'icon_bg'      => 'bg-gradient-to-br from-emerald-500 to-teal-600',
                'created_at'   => 'Update Available',
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Clear update cache so immediate probe can run.
     */
    public static function clearUpdateCache(): void
    {
        Cache::forget('sims_update_notification_cache');
    }
}
