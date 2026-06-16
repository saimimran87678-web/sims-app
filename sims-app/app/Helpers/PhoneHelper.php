<?php

namespace App\Helpers;

class PhoneHelper
{
    /**
     * Format a Pakistani phone number for WhatsApp.
     * Converts local format (03xx) to international format (923xx).
     * 
     * @param string|null $phone
     * @return string|null
     */
    public static function formatForWhatsApp(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Remove all non-numeric characters (spaces, dashes, parentheses)
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If starts with 0 (Pakistani local format like 03001234567)
        if (str_starts_with($phone, '0')) {
            // Replace leading 0 with 92 (Pakistan country code)
            $phone = '92' . substr($phone, 1);
        }
        
        // If starts with 92 already, keep as is
        // If it's some other format, return as-is (user might have entered international)

        return $phone;
    }

    /**
     * Generate a WhatsApp URL with pre-filled message.
     * 
     * @param string $phone Phone number (will be formatted if needed)
     * @param string $message The message to pre-fill
     * @return string The WhatsApp URL
     */
    public static function getWhatsAppUrl(string $phone, string $message): string
    {
        $formattedPhone = self::formatForWhatsApp($phone);
        $encodedMessage = urlencode($message);
        
        return "https://wa.me/{$formattedPhone}?text={$encodedMessage}";
    }

    /**
     * Generate an absence notification message.
     * 
     * @param string $studentName
     * @param string|int $rollNo
     * @param string $date (format: Y-m-d or any readable format)
     * @param string $schoolName
     * @return string
     */
    public static function getAbsentMessage(string $studentName, $rollNo, string $date, string $schoolName = null): string
    {
        $schoolName = $schoolName ?: \App\Models\Setting::get('institute_name', 'IMCB G-6/2');
        return "*Auto Generated Message*\n\nDear Parents,\nYour son {$studentName} (Roll No: {$rollNo}) is ABSENT from school today ({$date}).\nPlease contact the Class Teacher and give a valid reason.\n\n- {$schoolName} Administration";
    }

    /**
     * Generate a leave notification message.
     * 
     * @param string $studentName
     * @param string|int $rollNo
     * @param string $date (format: Y-m-d or any readable format)
     * @param string $schoolName
     * @return string
     */
    public static function getLeaveMessage(string $studentName, $rollNo, string $date, string $schoolName = null): string
    {
        $schoolName = $schoolName ?: \App\Models\Setting::get('institute_name', 'IMCB G-6/2');
        return "*Auto Generated Message*\n\nDear Parents,\nYour son {$studentName} (Roll No: {$rollNo}) is on LEAVE today ({$date}).\n\n- {$schoolName} Administration";
    }
}
