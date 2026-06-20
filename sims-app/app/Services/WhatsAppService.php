<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helpers\PhoneHelper;

class WhatsAppService
{
    protected string $baseUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.whatsapp.url', 'http://localhost:3000');
        $this->timeout = config('services.whatsapp.timeout', 30);
    }

    /**
     * Check if WhatsApp service is connected and ready.
     *
     * @return array{ready: bool, hasQr: bool, error: string|null}
     */
    public function getStatus(): array
    {
        try {
            $response = Http::timeout($this->timeout)->get("{$this->baseUrl}/status");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return ['ready' => false, 'hasQr' => false, 'error' => 'Service unavailable'];
        } catch (\Exception $e) {
            Log::error('WhatsApp Status Check Failed: ' . $e->getMessage());
            return ['ready' => false, 'hasQr' => false, 'error' => 'WhatsApp service server is offline. Please make sure the service is running.'];
        }
    }

    /**
     * Get QR code for authentication.
     *
     * @return array{success: bool, qr?: string, connected?: bool, message?: string}
     */
    public function getQrCode(): array
    {
        try {
            $response = Http::timeout($this->timeout)->get("{$this->baseUrl}/qr");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return ['success' => false, 'message' => 'Failed to get QR code'];
        } catch (\Exception $e) {
            Log::error('WhatsApp QR Fetch Failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'WhatsApp service server is offline. Could not fetch QR code.'];
        }
    }

    /**
     * Check if service is connected and ready to send.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        $status = $this->getStatus();
        return $status['ready'] ?? false;
    }

    /**
     * Send a single WhatsApp message.
     *
     * @param string $phone
     * @param string $message
     * @return array{success: bool, message?: string, error?: string}
     */
    public function sendMessage(string $phone, string $message): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/send", [
                    'phone' => PhoneHelper::formatForWhatsApp($phone),
                    'message' => $message
                ]);
            
            return $response->json();
        } catch (\Exception $e) {
            Log::error('WhatsApp Send Failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'WhatsApp service server is offline. Message kept in queue.'];
        }
    }

    /**
     * Send bulk WhatsApp messages.
     *
     * @param array $messages Array of ['phone' => '...', 'message' => '...']
     * @return array{success: bool, sent?: int, failed?: int, results?: array}
     */
    public function sendBulk(array $messages): array
    {
        try {
            // Format all phone numbers
            $formattedMessages = array_map(function ($item) {
                return [
                    'phone' => PhoneHelper::formatForWhatsApp($item['phone']),
                    'message' => $item['message']
                ];
            }, $messages);

            $response = Http::timeout($this->timeout * 2) // Longer timeout for bulk
                ->post("{$this->baseUrl}/send-bulk", [
                    'messages' => $formattedMessages
                ]);
            
            return $response->json();
        } catch (\Exception $e) {
            Log::error('WhatsApp Bulk Send Failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'WhatsApp service server is offline. Messages kept in queue.'];
        }
    }

    /**
     * Send media message (Image, Document, Voice).
     *
     * @param string $phone
     * @param string $caption
     * @param string $filePath Absolute path to file
     * @param bool $isVoice
     * @return array
     */
    public function sendMediaMessage(string $phone, string $caption, string $filePath, bool $isVoice = false): array
    {
        try {
            $formattedPhone = PhoneHelper::formatForWhatsApp($phone);
            
            $response = Http::timeout($this->timeout * 2)
                ->attach('file', file_get_contents($filePath), basename($filePath))
                ->post("{$this->baseUrl}/send-media", [
                    'phone' => $formattedPhone,
                    'caption' => $caption,
                    'isVoice' => $isVoice ? 'true' : 'false'
                ]);
            
            return $response->json();
        } catch (\Exception $e) {
            Log::error('WhatsApp Media Send Failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'WhatsApp service server is offline. Message kept in queue.'];
        }
    }

    /**
     * Send attendance notifications to parents.
     * Only sends ONE notification per student per day per type.
     *
     * @param array $students Array of student objects with id, phone, name, roll_no
     * @param string $status 'A' for absent, 'L' for leave
     * @param string $date Format: Y-m-d
     * @return array{sent: int, failed: int, skipped: array, alreadyNotified: int}
     */
    public function sendAttendanceNotifications(array $students, string $status, string $date): array
    {
        $messages = [];
        $skipped = [];
        $alreadyNotified = 0;
        $studentIdsToNotify = [];
        
        $type = $status === 'A' ? 'absent' : 'leave';

        foreach ($students as $student) {
            // Skip if no phone number
            if (empty($student['phone'])) {
                $skipped[] = $student['name'] ?? 'Unknown';
                continue;
            }

            // Check if already notified today
            $exists = \Illuminate\Support\Facades\DB::table('whatsapp_notifications')
                ->where('student_id', $student['id'])
                ->where('date', $date)
                ->where('type', $type)
                ->exists();

            if ($exists) {
                $alreadyNotified++;
                continue;
            }

            $gender = $student['gender'] ?? null;
            $message = $status === 'A'
                ? PhoneHelper::getAbsentMessage($student['name'], $student['roll_no'], $date, null, $gender)
                : PhoneHelper::getLeaveMessage($student['name'], $student['roll_no'], $date, null, $gender);

            $messages[] = [
                'phone' => $student['phone'],
                'message' => $message
            ];
            
            $studentIdsToNotify[] = $student['id'];
        }

        if (empty($messages)) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => $skipped, 'alreadyNotified' => $alreadyNotified];
        }

        $now = now();
        $queueRecords = [];
        $notificationRecords = [];

        // Insert into whatsapp_queue and whatsapp_notifications
        foreach ($messages as $index => $msg) {
            $studentId = $studentIdsToNotify[$index];

            $queueRecords[] = [
                'phone' => $msg['phone'],
                'message' => $msg['message'],
                'status' => 'pending',
                'student_id' => $studentId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $notificationRecords[] = [
                'student_id' => $studentId,
                'date' => $date,
                'type' => $type,
                'sent' => true, // We mark true to prevent duplicates, actual send is async
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($queueRecords)) {
            \Illuminate\Support\Facades\DB::table('whatsapp_queue')->insert($queueRecords);
            \Illuminate\Support\Facades\DB::table('whatsapp_notifications')->insertOrIgnore($notificationRecords);
        }

        return [
            'sent' => count($queueRecords), // Return as sent since they are successfully queued
            'failed' => 0,
            'skipped' => $skipped,
            'alreadyNotified' => $alreadyNotified
        ];
    }
    /**
     * Logout and destroy session.
     *
     * @return array{success: bool, message?: string}
     */
    public function logout(): array
    {
        try {
            $response = Http::timeout($this->timeout)->post("{$this->baseUrl}/logout");
            return $response->json();
        } catch (\Exception $e) {
            Log::error('WhatsApp Logout Failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'WhatsApp service server is offline.'];
        }
    }
}
