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
     * Check if a newer version or hotfix patch is available (cached for 30 minutes).
     *
     * @return array|null
     */
    public static function checkUpdateNotification(): ?array
    {
        return Cache::remember('sims_update_notification_cache', 1800, function () {
            try {
                $installedVer = Setting::getGlobal('installed_version') ?: config('app.version', '2.5.2');
                $manifestUrl = config('app.update_manifest_url', 'https://raw.githubusercontent.com/saimimran87678-web/sims-app/main/manifest.json');

                $ctx = stream_context_create([
                    'http' => [
                        'timeout'       => 2, // Non-blocking 2s timeout
                        'ignore_errors' => true,
                        'user_agent'    => 'SIMS-Update-Probe/2.5.2',
                    ],
                ]);

                $raw = @file_get_contents($manifestUrl, false, $ctx);
                if (!$raw) {
                    return null;
                }

                $clean = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
                $manifest = json_decode(trim($clean), true);

                if (!is_array($manifest) || empty($manifest['version'])) {
                    return null;
                }

                $latestVer = trim($manifest['version']);
                $installedChecksum = Setting::getGlobal('last_update_checksum', '');
                $manifestHash = strtolower(trim($manifest['checksum'] ?? $manifest['sha256'] ?? ''));

                $isNewerVersion = version_compare($latestVer, $installedVer, '>');
                $isSameVersion = version_compare($latestVer, $installedVer, '==');
                $isHotfixDiff = (!empty($manifestHash) && !empty($installedChecksum) && !in_array($installedChecksum, ['None', 'None (Initial installation)', 'Manual Patch']) && $manifestHash !== $installedChecksum);

                if ($isNewerVersion || ($isSameVersion && $isHotfixDiff)) {
                    $isPatch = !$isNewerVersion && $isHotfixDiff;
                    $title = $isPatch ? "⚡ Hotfix Patch Available (v{$latestVer})" : "🚀 New Version Available (v{$latestVer})";
                    $notes = $manifest['changelog'] ?? 'Includes performance optimizations, bug fixes, and security patches.';

                    return [
                        'id'           => 'update_' . $latestVer . '_' . substr($manifestHash, 0, 6),
                        'type'         => 'update',
                        'title'        => $title,
                        'message'      => $notes,
                        'version'      => $latestVer,
                        'current'      => $installedVer,
                        'action_url'   => route('admin.settings') . '#updates-tab',
                        'action_label' => 'Update System Now',
                        'badge'        => $isPatch ? 'Hotfix' : "v{$latestVer}",
                        'badge_color'  => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                        'icon_bg'      => 'bg-gradient-to-br from-emerald-500 to-teal-600',
                        'created_at'   => 'Update Available',
                    ];
                }
            } catch (\Throwable) {}

            return null;
        });
    }

    /**
     * Clear update cache so immediate probe can run.
     */
    public static function clearUpdateCache(): void
    {
        Cache::forget('sims_update_notification_cache');
    }
}
