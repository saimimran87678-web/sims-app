<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RfidCardService
{
    public const INSTITUTION_CODE = 'ICB1';
    public const PAYLOAD_LENGTH_BYTES = 48;

    /**
     * Compute 8-byte (16 hex chars) HMAC-SHA256 signature for anti-tamper verification.
     */
    public static function computeHmac(string $cardUid, int $studentId, string $rollNo): string
    {
        $appKey = config('app.key') ?: 'base64:AdminovaSecretKey2026';
        $normalizedUid = strtoupper(str_replace([':', ' ', '-'], '', $cardUid));
        $data = $normalizedUid . '|' . $studentId . '|' . trim($rollNo);

        return substr(hash_hmac('sha256', $data, $appKey), 0, 16);
    }

    /**
     * Verify whether a card's HMAC signature matches the student's authentic record.
     */
    public static function verifyHmac(string $cardUid, int $studentId, string $rollNo, string $signatureHex): bool
    {
        $expected = self::computeHmac($cardUid, $studentId, $rollNo);
        return hash_equals($expected, strtolower(trim($signatureHex)));
    }

    /**
     * Generate the complete 48-byte binary and hex payload for writing to MIFARE 1K / NTAG213 sectors.
     */
    public function generatePayload(Student $student, string $cardUid): array
    {
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $classId = (int) ($student->getClassIdAttribute() ?? 0);
        $rollNo = (int) ($student->getRollNoAttribute() ?? 0);
        $rollNoStr = (string) $rollNo;
        $admissionNo = substr($student->admission_no ?? '', 0, 12);

        $hmacHex = self::computeHmac($cardUid, $student->id, $rollNoStr);
        $hmacBin = hex2bin($hmacHex);

        // Binary Packing (48 Bytes total):
        // [4B: Inst Code] [4B: Student ID UInt32BE] [2B: Class ID UInt16BE] [2B: Roll No UInt16BE]
        // [12B: Admission No] [8B: HMAC Signature] [16B: Reserved/Session Data]
        $header = substr(str_pad(self::INSTITUTION_CODE, 4, "\0"), 0, 4);
        $packedIds = pack('Nnn', $student->id, $classId, $rollNo);
        $paddedAdmission = substr(str_pad($admissionNo, 12, "\0"), 0, 12);
        $reserved = str_pad("SES:" . $activeSessionId, 16, "\0");

        $binaryPayload = $header . $packedIds . $paddedAdmission . $hmacBin . $reserved;
        $hexPayload = bin2hex($binaryPayload);

        return [
            'card_uid' => strtoupper($cardUid),
            'student_id' => $student->id,
            'student_name' => $student->name,
            'admission_no' => $student->admission_no,
            'roll_no' => $rollNoStr,
            'class_id' => $classId,
            'class_name' => $student->class?->name ?? 'Unassigned',
            'hmac_hex' => $hmacHex,
            'payload_hex' => $hexPayload,
            'payload_bytes' => strlen($binaryPayload),
        ];
    }

    /**
     * Decode a 48-byte hex payload read back from an RFID card.
     */
    public function decodePayload(string $hexPayload): ?array
    {
        $bin = @hex2bin(trim($hexPayload));
        if (!$bin || strlen($bin) < self::PAYLOAD_LENGTH_BYTES) {
            return null;
        }

        $instCode = trim(substr($bin, 0, 4));
        $unpacked = unpack('Nstudent_id/nclass_id/nroll_no', substr($bin, 4, 8));
        $admissionNo = trim(substr($bin, 12, 12));
        $hmacHex = bin2hex(substr($bin, 24, 8));

        return [
            'inst_code' => $instCode,
            'student_id' => $unpacked['student_id'] ?? 0,
            'class_id' => $unpacked['class_id'] ?? 0,
            'roll_no' => (string) ($unpacked['roll_no'] ?? 0),
            'admission_no' => $admissionNo,
            'hmac_hex' => $hmacHex,
        ];
    }

    /**
     * Arm a specific hardware reader for writing a card to a student.
     * Stored in Cache with a 90-second timeout.
     */
    public function armDeviceForWriting(string $deviceId, int $studentId): void
    {
        Cache::put("rfid_write_job_{$deviceId}", $studentId, now()->addSeconds(90));
        Cache::put("rfid_device_mode_{$deviceId}", 'write', now()->addSeconds(90));
    }

    /**
     * Arm a specific hardware reader for card inspection and chip verification.
     * Stored in Cache with a 10-minute timeout.
     */
    public function armDeviceForInspection(string $deviceId): void
    {
        Cache::forget("rfid_write_job_{$deviceId}");
        Cache::put("rfid_device_mode_{$deviceId}", 'inspect', now()->addMinutes(10));
    }

    /**
     * Disarm inspection mode back to normal attendance scanning mode.
     */
    public function disarmInspection(string $deviceId): void
    {
        if (Cache::get("rfid_device_mode_{$deviceId}") === 'inspect') {
            Cache::put("rfid_device_mode_{$deviceId}", 'attendance', now()->addHours(24));
        }
    }

    /**
     * Disarm a device back to normal attendance scanning mode.
     */
    public function disarmDevice(string $deviceId): void
    {
        Cache::forget("rfid_write_job_{$deviceId}");
        Cache::put("rfid_device_mode_{$deviceId}", 'attendance', now()->addHours(24));
    }

    /**
     * Get the active operational mode of a device: 'attendance', 'write', or 'inspect'.
     */
    public function getDeviceMode(string $deviceId): string
    {
        // If a write job is pending, prioritize write mode
        if (Cache::has("rfid_write_job_{$deviceId}")) {
            return 'write';
        }

        return Cache::get("rfid_device_mode_{$deviceId}", 'attendance');
    }

    /**
     * Retrieve any active write job assigned to a device.
     */
    public function getPendingWriteJob(string $deviceId): ?int
    {
        return Cache::get("rfid_write_job_{$deviceId}");
    }

    /**
     * Store inspection result for real-time Livewire UI polling.
     */
    public function recordInspectResult(string $deviceId, array $result): void
    {
        Cache::put("rfid_inspect_result_{$deviceId}", array_merge($result, [
            'timestamp' => now()->timestamp,
        ]), now()->addMinutes(2));
    }

    /**
     * Retrieve and clear the latest inspection result for a device.
     */
    public function pullInspectResult(string $deviceId): ?array
    {
        return Cache::pull("rfid_inspect_result_{$deviceId}");
    }

    /**
     * Update heartbeat timestamp for a device and register in active devices set.
     */
    public function recordHeartbeat(string $deviceId, ?string $ip = null, string $rfidStatus = 'ok', ?string $rfidVersion = null): void
    {
        $payload = [
            'id' => $deviceId,
            'last_seen' => now()->timestamp,
            'ip' => $ip,
            'rfid_status' => $rfidStatus,
            'rfid_version' => $rfidVersion,
        ];

        Cache::put("rfid_device_online_{$deviceId}", $payload, now()->addMinutes(3));

        $devices = Cache::get('rfid_active_devices', []);
        $devices[$deviceId] = $payload;
        Cache::put('rfid_active_devices', $devices, now()->addMinutes(10));
    }

    /**
     * Check if a device is online (seen in last 60s).
     */
    public function isDeviceOnline(string $deviceId): bool
    {
        $info = Cache::get("rfid_device_online_{$deviceId}");
        if (!$info) return false;
        return (now()->timestamp - ($info['last_seen'] ?? 0)) <= 60;
    }

    /**
     * Get comprehensive device hardware and connectivity status.
     */
    public function getDeviceStatus(string $deviceId): array
    {
        $info = Cache::get("rfid_device_online_{$deviceId}");
        if (!$info) {
            return [
                'online' => false,
                'rfid_status' => 'offline',
                'rfid_version' => null,
                'ip' => null,
                'last_seen' => 0,
            ];
        }

        $isOnline = (now()->timestamp - ($info['last_seen'] ?? 0)) <= 60;
        return [
            'online' => $isOnline,
            'rfid_status' => $isOnline ? ($info['rfid_status'] ?? 'ok') : 'offline',
            'rfid_version' => $info['rfid_version'] ?? null,
            'ip' => $info['ip'] ?? null,
            'last_seen' => $info['last_seen'] ?? 0,
        ];
    }

    /**
     * Record write operation result (success or failed) for UI polling.
     */
    public function recordWriteResult(string $deviceId, string $status, int $studentId, ?string $cardUid = null, ?string $error = null): void
    {
        Cache::put("rfid_write_result_{$deviceId}", [
            'status' => $status,
            'student_id' => $studentId,
            'card_uid' => $cardUid,
            'error' => $error,
            'timestamp' => now()->timestamp,
        ], now()->addMinutes(2));
    }

    /**
     * Retrieve and clear the latest write result for a device.
     */
    public function pullWriteResult(string $deviceId): ?array
    {
        return Cache::pull("rfid_write_result_{$deviceId}");
    }

    /**
     * Get list of all currently active/online devices.
     */
    public function getActiveDevices(): array
    {
        $devices = Cache::get('rfid_active_devices', []);
        $now = now()->timestamp;
        $active = [];
        foreach ($devices as $id => $data) {
            if (($now - ($data['last_seen'] ?? 0)) <= 60) {
                $active[] = $data;
            }
        }
        return $active;
    }

    /**
     * Get the ID of the first available online reader, or null.
     */
    public function getFirstOnlineDevice(): ?string
    {
        $active = $this->getActiveDevices();
        return !empty($active) ? $active[0]['id'] : null;
    }
}
