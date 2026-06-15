<?php

namespace App\Services;

class LicenseVerifier
{
    /**
     * Normalize the expires_at date into the ISO-8601 UTC string format used for signature verification.
     *
     * @param string $licenseKey
     * @param string|null $expiresAt
     * @return string
     */
    public static function normalizeExpiresAt(string $licenseKey, ?string $expiresAt): string
    {
        if (empty($expiresAt)) {
            return '';
        }

        // Parse date explicitly in UTC timezone to avoid timezone shift on local setups
        $dt = \Carbon\Carbon::parse($expiresAt, 'UTC');
        
        $ms = $dt->format('v');
        if ($ms === '000' || $ms === '0') {
            // SQLite strips milliseconds on storage; retrieve them from the license key timestamp suffix
            $parts = explode('-', $licenseKey);
            $timestamp = end($parts);
            if (is_numeric($timestamp) && strlen($timestamp) > 3) {
                $ms = substr($timestamp, -3);
            }
        }
        
        $ms = str_pad($ms, 3, '0', STR_PAD_RIGHT);
        
        return $dt->format('Y-m-d\TH:i:s') . '.' . $ms . 'Z';
    }

    /**
     * Compute the local integrity hash for a license record.
     *
     * @param string $licenseKey
     * @param string|null $expiresAt
     * @param string $status
     * @param string $schoolId
     * @return string
     */
    public static function computeIntegrityHash(string $licenseKey, ?string $expiresAt, string $status, string $schoolId): string
    {
        $integrityKey = config('services.license.integrity_key');
        if (empty($integrityKey)) {
            // Fallback to APP_KEY if integrity key is not configured
            $integrityKey = config('app.key');
        }

        $normalizedExpires = self::normalizeExpiresAt($licenseKey, $expiresAt);

        $payload = implode('|', [
            $licenseKey,
            $normalizedExpires,
            $status,
            $schoolId
        ]);

        return hash_hmac('sha256', $payload, $integrityKey);
    }

    /**
     * Verify the integrity hash of a stored license record.
     *
     * @param object $licenseRecord
     * @return bool
     */
    public static function verifyIntegrity($licenseRecord): bool
    {
        if (!$licenseRecord) {
            return false;
        }

        // Decrypt fields if they are stored encrypted in the database
        try {
            $licenseKey = decrypt($licenseRecord->license_key);
            $status = decrypt($licenseRecord->status);
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
     * Verify the cryptographic RSA signature of the license parameters.
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

        // Standardize PEM format lines
        if (strpos($publicKeyPem, '-----BEGIN PUBLIC KEY-----') === false) {
            $publicKeyPem = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($publicKeyPem, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        }

        $normalizedExpires = self::normalizeExpiresAt($licenseKey, $expiresAt);

        $payload = implode('|', [
            $licenseKey,
            $normalizedExpires,
            $status
        ]);

        $signature = base64_decode($signatureBase64);
        if ($signature === false) {
            return false;
        }

        $result = openssl_verify(
            $payload,
            $signature,
            $publicKeyPem,
            OPENSSL_ALGO_SHA256
        );

        return $result === 1;
    }
}
