<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helpers\PhoneHelper;

class WhatsAppService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = \App\Models\Setting::get('whatsapp_service_url', config('services.whatsapp.url', 'http://localhost:3000'));
        $this->apiKey = \App\Models\Setting::get('whatsapp_api_key', config('services.whatsapp.key', 'whatsapp12345'));
        $this->timeout = config('services.whatsapp.timeout', 30);
    }

    /**
     * Get pre-configured HTTP client with API Key headers.
     */
    protected function client(int $timeoutMultiplier = 1)
    {
        return Http::timeout($this->timeout * $timeoutMultiplier)
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'Accept' => 'application/json'
            ]);
    }

    /**
     * Test connection to a WhatsApp service URL with API Key.
     */
    public function testConnection(?string $url = null, ?string $apiKey = null): array
    {
        $targetUrl = rtrim($url ?: $this->baseUrl, '/');
        $targetKey = $apiKey !== null ? $apiKey : $this->apiKey;

        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'x-api-key' => $targetKey,
                    'Accept' => 'application/json'
                ])->get("{$targetUrl}/status");

            if ($response->successful()) {
                $data = $response->json();
                $isReady = $data['ready'] ?? $data['isReady'] ?? false;
                $user = $data['user'] ?? null;
                $userFormatted = $user ? ' (' . preg_replace('/[^0-9]/', '', explode('@', $user)[0]) . ')' : '';

                return [
                    'success' => true,
                    'message' => $isReady 
                        ? "Gateway connected successfully! Engine is active and ready to send messages{$userFormatted}."
                        : 'Gateway connected successfully! Engine is online, waiting for device linkage via QR code or pairing code.',
                    'data' => $data
                ];
            }

            if ($response->status() === 401) {
                return [
                    'success' => false,
                    'error' => 'Authentication failed. The security key entered does not match your system configuration.'
                ];
            }

            if ($response->status() === 404) {
                return [
                    'success' => false,
                    'error' => 'Gateway endpoint not found at this address. Please verify the URL.'
                ];
            }

            return [
                'success' => false,
                'error' => 'Gateway response error. Unable to verify engine status.'
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'success' => false,
                'error' => 'Unable to establish connection with the gateway. Please check if the engine is started and reachable.'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Unable to establish connection with the gateway. Please check if the engine is started and reachable.'
            ];
        }
    }

    /**
     * Check if WhatsApp service is connected and ready.
     *
     * @return array{ready: bool, hasQr: bool, error: string|null}
     */
    public function getStatus(): array
    {
        try {
            $response = $this->client()->get("{$this->baseUrl}/status");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return ['ready' => false, 'hasQr' => false, 'error' => 'Messaging engine is unresponsive.'];
        } catch (\Exception $e) {
            Log::error('WhatsApp Status Check Failed: ' . $e->getMessage());
            return ['ready' => false, 'hasQr' => false, 'error' => 'Messaging engine is currently offline. Please ensure the gateway application is running.'];
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
            $response = $this->client()->get("{$this->baseUrl}/qr");
            
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
     * Request 8-Digit Pairing Code for phone linking.
     *
     * @param string $phone
     * @return array{success: bool, pairingCode?: string, rawCode?: string, error?: string, message?: string}
     */
    public function requestPairingCode(string $phone): array
    {
        try {
            $formattedPhone = PhoneHelper::formatForWhatsApp($phone);
            $response = $this->client()->post("{$this->baseUrl}/pairing-code", [
                'phone' => $formattedPhone
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'success' => false,
                'error' => $response->json('error') ?? $response->json('message') ?? ('HTTP ' . $response->status())
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp Pairing Code Request Failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to request pairing code: ' . $e->getMessage()];
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
            $response = $this->client()->post("{$this->baseUrl}/send", [
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

            $response = $this->client(2)->post("{$this->baseUrl}/send-bulk", [
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
            
            $response = $this->client(2)
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
                ? PhoneHelper::getAbsentMessage($student['name'], $student['roll_no'], $date, null, $gender, $student['id'])
                : PhoneHelper::getLeaveMessage($student['name'], $student['roll_no'], $date, null, $gender, $student['id']);

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
     * Send fee payment confirmation.
     */
    public function sendPaymentNotification($payment): void
    {
        $student = $payment->student;
        if (empty($student->phone)) {
            return;
        }

        $formattedPeriod = \Carbon\Carbon::parse($payment->record->period . '-01')->format('F Y');
        $link = url('/v/' . $payment->record->access_token);
        
        $message = PhoneHelper::getPaymentMessage(
            $student->name,
            $payment->amount,
            $formattedPeriod,
            $payment->record->balance,
            null,
            $link,
            $student->id
        );

        $now = now();
        \Illuminate\Support\Facades\DB::table('whatsapp_queue')->insert([
            'phone' => $student->phone,
            'message' => $message,
            'status' => 'pending',
            'student_id' => $student->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Send bulk fee reminders.
     * @param array $records Array of FeeRecord models
     */
    public function sendFeeReminders($records): array
    {
        $queueRecords = [];
        $now = now();

        foreach ($records as $record) {
            $student = $record->student;
            if (empty($student->phone) || $record->balance <= 0) {
                continue;
            }

            $formattedPeriod = \Carbon\Carbon::parse($record->period . '-01')->format('F Y');
            $dueDate = $record->due_date->format('d M, Y');
            $link = url('/v/' . $record->access_token);

            $message = PhoneHelper::getFeeReminderMessage(
                $student->name,
                $record->balance,
                $formattedPeriod,
                $dueDate,
                null,
                $link,
                $student->id
            );

            $queueRecords[] = [
                'phone' => $student->phone,
                'message' => $message,
                'status' => 'pending',
                'student_id' => $student->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($queueRecords)) {
            \Illuminate\Support\Facades\DB::table('whatsapp_queue')->insert($queueRecords);
        }

        return [
            'sent' => count($queueRecords),
            'failed' => 0
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
            $response = $this->client()->post("{$this->baseUrl}/logout");
            return $response->json();
        } catch (\Exception $e) {
            Log::error('WhatsApp Logout Failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'WhatsApp service server is offline.'];
        }
    }
}
