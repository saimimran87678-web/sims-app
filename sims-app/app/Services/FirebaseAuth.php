<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseAuth
{
    /**
     * Exchange a Firebase Refresh Token for an ID Token.
     *
     * @param string $refreshToken
     * @return array|null Returns [id_token, refresh_token] or null on failure
     */
    public static function fetchIdToken(string $refreshToken): ?array
    {
        $apiKey = config('services.firebase.api_key');
        if (empty($apiKey)) {
            Log::error('Firebase API key is not configured.');
            return null;
        }

        try {
            $response = Http::withoutVerifying()->asForm()->post("https://securetoken.googleapis.com/v1/token?key={$apiKey}", [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'id_token' => $data['id_token'],
                    'refresh_token' => $data['refresh_token'] ?? $refreshToken, // Return rotated refresh token if provided
                ];
            }

            Log::error('Firebase Token exchange failed: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Firebase Connection error during token fetch: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Query the Firestore REST API to retrieve license details.
     *
     * @param string $licenseKey
     * @param string|null $idToken
     * @return array|null
     */
    public static function queryLicenseFirestore(string $licenseKey, ?string $idToken = null): ?array
    {
        $projectId = config('services.firebase.project_id');
        if (empty($projectId)) {
            Log::error('Firebase Project ID is not configured.');
            return null;
        }

        try {
            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/licenses/{$licenseKey}";
            
            $request = Http::withoutVerifying();
            if (!empty($idToken)) {
                $request = $request->withToken($idToken);
            }

            $response = $request->get($url);

            if ($response->successful()) {
                $fields = $response->json('fields');
                
                // Helper to extract typed values from Firestore JSON payload
                $extractValue = function ($field) {
                    if (is_null($field)) return null;
                    return $field['stringValue'] ?? $field['integerValue'] ?? $field['booleanValue'] ?? null;
                };

                // Helper to extract typed array values from Firestore JSON payload
                $extractArray = function ($field) {
                    if (empty($field['arrayValue']['values'])) return null;
                    return array_map(function ($item) {
                        return $item['stringValue'] ?? null;
                    }, $field['arrayValue']['values']);
                };

                return [
                    'status' => $extractValue($fields['status'] ?? null),
                    'plan' => $extractValue($fields['plan'] ?? null),
                    'expires_at' => $extractValue($fields['expires_at'] ?? null),
                    'rsa_signature' => $extractValue($fields['rsa_signature'] ?? null),
                    'school_id' => $extractValue($fields['school_id'] ?? null),
                    'allowed_domain' => $extractValue($fields['allowed_domain'] ?? null),
                    'offline_grace' => isset($fields['offline_grace']) ? intval($extractValue($fields['offline_grace'])) : 7,
                    'enabled_modules' => $extractArray($fields['enabled_modules'] ?? null) ?: ['fees', 'exams', 'attendance', 'whatsapp', 'reports'],
                    'broadcast_announcement' => $extractValue($fields['broadcast_announcement'] ?? null),
                    'schedule_type_policy' => $extractValue($fields['schedule_type_policy'] ?? null) ?: 'configurable',
                    'config_version' => isset($fields['config_version']) ? intval($extractValue($fields['config_version'])) : 1,
                    'bound_machine_uuid' => $extractValue($fields['bound_machine_uuid'] ?? null) ?: $extractValue($fields['telemetry']['mapValue']['fields']['bound_machine_uuid'] ?? null),
                ];
            }

            Log::error('Firestore license lookup failed: ' . $response->status() . ' - ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Firebase Connection error during Firestore query: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Report telemetry heartbeat (machine UUID, hostname, IP, location) to Firestore.
     *
     * @param string $licenseKey
     * @param array $telemetry
     * @param string|null $idToken
     * @return bool
     */
    public static function sendTelemetryHeartbeat(string $licenseKey, array $telemetry, ?string $idToken = null): bool
    {
        $projectId = config('services.firebase.project_id');
        if (empty($projectId) || empty($licenseKey)) {
            return false;
        }

        try {
            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/licenses/{$licenseKey}";

            $fieldPaths = [
                'bound_machine_uuid',
                'hostname',
                'public_ip',
                'location',
                'isp',
                'last_active_at',
                'installed_version',
                'telemetry',
            ];

            $queryString = implode('&', array_map(fn($p) => "updateMask.fieldPaths=" . urlencode($p), $fieldPaths));
            $fullUrl = "{$url}?{$queryString}";

            $now = \Carbon\Carbon::now()->toIso8601String();
            $firestoreFields = [
                'bound_machine_uuid' => ['stringValue' => (string) ($telemetry['bound_machine_uuid'] ?? '')],
                'hostname'           => ['stringValue' => (string) ($telemetry['hostname'] ?? '')],
                'public_ip'          => ['stringValue' => (string) ($telemetry['public_ip'] ?? '')],
                'location'           => ['stringValue' => (string) ($telemetry['location'] ?? '')],
                'isp'                => ['stringValue' => (string) ($telemetry['isp'] ?? '')],
                'last_active_at'     => ['stringValue' => (string) ($telemetry['last_active_at'] ?? $now)],
                'installed_version'  => ['stringValue' => (string) ($telemetry['installed_version'] ?? '2.5.2')],
                'telemetry'          => [
                    'mapValue' => [
                        'fields' => [
                            'bound_machine_uuid' => ['stringValue' => (string) ($telemetry['bound_machine_uuid'] ?? '')],
                            'hostname'           => ['stringValue' => (string) ($telemetry['hostname'] ?? '')],
                            'public_ip'          => ['stringValue' => (string) ($telemetry['public_ip'] ?? '')],
                            'location'           => ['stringValue' => (string) ($telemetry['location'] ?? '')],
                            'isp'                => ['stringValue' => (string) ($telemetry['isp'] ?? '')],
                            'last_active_at'     => ['stringValue' => (string) ($telemetry['last_active_at'] ?? $now)],
                            'installed_version'  => ['stringValue' => (string) ($telemetry['installed_version'] ?? '2.5.2')],
                        ],
                    ],
                ],
            ];

            $request = Http::withoutVerifying();
            if (!empty($idToken)) {
                $request = $request->withToken($idToken);
            }

            $response = $request->patch($fullUrl, ['fields' => $firestoreFields]);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::debug('Telemetry heartbeat sync failed: ' . $e->getMessage());
            return false;
        }
    }
}
