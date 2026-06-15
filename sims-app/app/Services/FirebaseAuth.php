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
     * @param string $idToken
     * @return array|null
     */
    public static function queryLicenseFirestore(string $licenseKey, string $idToken): ?array
    {
        $projectId = config('services.firebase.project_id');
        if (empty($projectId)) {
            Log::error('Firebase Project ID is not configured.');
            return null;
        }

        try {
            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/licenses/{$licenseKey}";
            
            $response = Http::withoutVerifying()->withToken($idToken)->get($url);

            if ($response->successful()) {
                $fields = $response->json('fields');
                
                // Helper to extract typed values from Firestore JSON payload
                $extractValue = function ($field) {
                    if (is_null($field)) return null;
                    return $field['stringValue'] ?? $field['integerValue'] ?? $field['booleanValue'] ?? null;
                };

                return [
                    'status' => $extractValue($fields['status'] ?? null),
                    'plan' => $extractValue($fields['plan'] ?? null),
                    'expires_at' => $extractValue($fields['expires_at'] ?? null),
                    'rsa_signature' => $extractValue($fields['rsa_signature'] ?? null),
                    'school_id' => $extractValue($fields['school_id'] ?? null),
                    'allowed_domain' => $extractValue($fields['allowed_domain'] ?? null),
                    'offline_grace' => isset($fields['offline_grace']) ? intval($extractValue($fields['offline_grace'])) : 7,
                ];
            }

            Log::error('Firestore license lookup failed: ' . $response->status() . ' - ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Firebase Connection error during Firestore query: ' . $e->getMessage());
        }

        return null;
    }
}
