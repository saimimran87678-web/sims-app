<?php

namespace App\Services;

use Carbon\Carbon;

class LicenseVerifier
{
    /**
     * Normalize expires_at to a simple UTC string: "YYYY-MM-DD HH:mm:ss"
     *
     * This format MUST match exactly what the admin panel JavaScript signs with
     * (formatToUTCString in firebase_admin_panel.html).
     *
     * @param string|null $expiresAt
     * @return string
     */
    public static function normalizeExpiresAt(?string $expiresAt): string
    {
        if (empty($expiresAt)) {
            return '';
        }

        // Always parse and output in UTC, simple "YYYY-MM-DD HH:mm:ss" — no T, no ms, no Z
        return Carbon::parse($expiresAt, 'UTC')->format('Y-m-d H:i:s');
    }

    /**
     * Build the canonical payload string for RSA signature verification.
     * Format: "licenseKey|YYYY-MM-DD HH:mm:ss|status"
     *
     * @param string $licenseKey
     * @param string|null $expiresAt
     * @param string $status
     * @return string
     */
    public static function buildPayload(string $licenseKey, ?string $expiresAt, string $status): string
    {
        return implode('|', [
            $licenseKey,
            self::normalizeExpiresAt($expiresAt),
            $status,
        ]);
    }

    /**
     * Compute the local HMAC-SHA256 integrity hash for a license record.
     * Used to detect local SQLite database tampering.
     *
     * @param string $licenseKey
     * @param string|null $expiresAt
     * @param string $status
     * @param string $schoolId
     * @return string
     */
    public static function computeIntegrityHash(string $licenseKey, ?string $expiresAt, string $status, string $schoolId): string
    {
        $integrityKey = config('services.license.integrity_key') ?: config('app.key');

        $payload = implode('|', [
            $licenseKey,
            self::normalizeExpiresAt($expiresAt),
            $status,
            $schoolId,
        ]);

        return hash_hmac('sha256', $payload, $integrityKey);
    }

    /**
     * Verify the HMAC integrity hash of a stored license record.
     *
     * @param object $licenseRecord
     * @return bool
     */
    public static function verifyIntegrity($licenseRecord): bool
    {
        if (!$licenseRecord) {
            return false;
        }

        try {
            $licenseKey = decrypt($licenseRecord->license_key);
            $status     = decrypt($licenseRecord->status);
        } catch (\Exception $e) {
            return false;
        }

        $computed = self::computeIntegrityHash(
            $licenseKey,
            $licenseRecord->expires_at,
            $status,
            $licenseRecord->school_id
        );

        return hash_equals($licenseRecord->integrity_hash, $computed);
    }

    /**
     * Verify the RSA-SHA256 cryptographic signature of a license.
     * The payload format matches what the admin panel JS signs:
     *   "licenseKey|YYYY-MM-DD HH:mm:ss|status"
     *
     * @param string $licenseKey
     * @param string|null $expiresAt
     * @param string $status
     * @param string $signatureBase64
     * @return bool
     */
    public static function verifyRsaSignature(string $licenseKey, ?string $expiresAt, string $status, string $signatureBase64): bool
    {
        $publicKeyPem = config('services.license.rsa_public_key');
        if (empty($publicKeyPem)) {
            return false;
        }

        // Reconstruct PEM header/footer if stored as a flat string (env \n → actual newlines)
        $publicKeyPem = str_replace('\\n', "\n", $publicKeyPem);
        if (strpos($publicKeyPem, '-----BEGIN PUBLIC KEY-----') === false) {
            $publicKeyPem = "-----BEGIN PUBLIC KEY-----\n"
                . wordwrap(trim($publicKeyPem), 64, "\n", true)
                . "\n-----END PUBLIC KEY-----";
        }

        $payload   = self::buildPayload($licenseKey, $expiresAt, $status);
        $signature = base64_decode($signatureBase64, true);

        if ($signature === false) {
            return false;
        }

        $result = openssl_verify($payload, $signature, $publicKeyPem, OPENSSL_ALGO_SHA256);

        return $result === 1;
    }
}
