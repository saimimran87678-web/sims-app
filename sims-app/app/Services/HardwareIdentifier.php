<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HardwareIdentifier
{
    /**
     * Get the Motherboard BIOS UUID or Windows MachineGuid.
     */
    public static function getMachineUuid(): string
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        // 1. Try Motherboard BIOS UUID via CIM/WMI
        $output = trim((string) @shell_exec('powershell -NoProfile -Command "(Get-CimInstance Win32_ComputerSystemProduct).UUID" 2>NUL'));
        if (!empty($output) && strlen($output) >= 16 && $output !== 'FFFFFFFF-FFFF-FFFF-FFFF-FFFFFFFFFFFF') {
            return $cached = strtoupper($output);
        }

        // 2. Fallback to Windows MachineGuid in Registry
        $guid = trim((string) @shell_exec('powershell -NoProfile -Command "(Get-ItemProperty -Path \'HKLM:\SOFTWARE\Microsoft\Cryptography\').MachineGuid" 2>NUL'));
        if (!empty($guid) && strlen($guid) >= 16) {
            return $cached = strtoupper($guid);
        }

        return $cached = 'UNKNOWN-MACHINE-UUID';
    }

    /**
     * Get computer hostname.
     */
    public static function getHostname(): string
    {
        return gethostname() ?: (getenv('COMPUTERNAME') ?: 'UNKNOWN-HOST');
    }

    /**
     * Get public IP, location, and ISP with 1-hour cache.
     */
    public static function getNetworkTelemetry(): array
    {
        return Cache::remember('sims_network_telemetry', 3600, function () {
            $telemetry = [
                'public_ip' => null,
                'location'  => null,
                'isp'       => null,
            ];

            // Primary: ip-api.com
            try {
                $res = Http::timeout(3)->get('http://ip-api.com/json/');
                if ($res->successful()) {
                    $data = $res->json();
                    $telemetry['public_ip'] = $data['query'] ?? null;
                    $loc = trim(($data['city'] ?? '') . ', ' . ($data['country'] ?? ''), ', ');
                    $telemetry['location']  = !empty($loc) ? $loc : null;
                    $telemetry['isp']       = $data['isp'] ?? null;
                    return $telemetry;
                }
            } catch (\Throwable $e) {
                // Secondary fallback: api.ipify.org
                try {
                    $res = Http::timeout(2)->get('https://api.ipify.org?format=json');
                    if ($res->successful()) {
                        $telemetry['public_ip'] = $res->json('ip');
                    }
                } catch (\Throwable $e2) {
                    Log::debug('Network telemetry lookup timed out or offline: ' . $e2->getMessage());
                }
            }

            return $telemetry;
        });
    }
}
