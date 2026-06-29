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
     * Resolve child relation string for messages based on gender.
     */
    private static function resolveRelation(?string $gender): string
    {
        $gender = strtolower($gender ?? '');
        if ($gender === 'male') return 'son';
        if ($gender === 'female') return 'daughter';
        return 'child';
    }

    /**
     * Helper to process the template.
     */
    private static function processTemplate(string $template, array $data): string
    {
        $replacements = [
            '{student_name}' => $data['student_name'] ?? '',
            '{roll_no}' => $data['roll_no'] ?? '',
            '{date}' => $data['date'] ?? '',
            '{time}' => $data['time'] ?? '',
            '{school_name}' => $data['school_name'] ?? '',
            '{relation}' => self::resolveRelation($data['gender'] ?? null),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Generate an absence notification message.
     * 
     * @param string $studentName
     * @param string|int $rollNo
     * @param string $date (format: Y-m-d or any readable format)
     * @param string $schoolName
     * @param string|null $gender
     * @return string
     */
    public static function getAbsentMessage(string $studentName, $rollNo, string $date, string $schoolName = null, string $gender = null): string
    {
        $schoolName = $schoolName ?: \App\Models\Setting::get('institute_name', 'IMCB G-6/2');
        $defaultTemplate = "*Auto Generated Message*\n\nDear Parents,\nYour {relation} {student_name} (Roll No: {roll_no}) is ABSENT from school today ({date}).\nPlease contact the Class Teacher and give a valid reason.\n\n- {school_name} Administration";
        $template = \App\Models\Setting::get('whatsapp_template_absent', $defaultTemplate);

        return self::processTemplate($template, [
            'student_name' => $studentName,
            'roll_no' => $rollNo,
            'date' => $date,
            'school_name' => $schoolName,
            'gender' => $gender,
        ]);
    }

    /**
     * Generate a leave notification message.
     * 
     * @param string $studentName
     * @param string|int $rollNo
     * @param string $date (format: Y-m-d or any readable format)
     * @param string $schoolName
     * @param string|null $gender
     * @return string
     */
    public static function getLeaveMessage(string $studentName, $rollNo, string $date, string $schoolName = null, string $gender = null): string
    {
        $schoolName = $schoolName ?: \App\Models\Setting::get('institute_name', 'IMCB G-6/2');
        $defaultTemplate = "*Auto Generated Message*\n\nDear Parents,\nYour {relation} {student_name} (Roll No: {roll_no}) is on LEAVE today ({date}).\n\n- {school_name} Administration";
        $template = \App\Models\Setting::get('whatsapp_template_leave', $defaultTemplate);

        return self::processTemplate($template, [
            'student_name' => $studentName,
            'roll_no' => $rollNo,
            'date' => $date,
            'school_name' => $schoolName,
            'gender' => $gender,
        ]);
    }

    public static function getLateMessage(string $studentName, $rollNo, string $time, string $schoolName = null, string $gender = null): string
    {
        $schoolName = $schoolName ?: \App\Models\Setting::get('institute_name', 'IMCB G-6/2');
        $defaultTemplate = "*Urgent Message*\n\nDear Parents,\nWe noticed that your {relation} {student_name} (Roll No: {roll_no}) was marked absent/leave, but has now arrived late at school today at {time}.\nPlease ensure they arrive on time in the future to avoid any warning.\n\n- {school_name} Administration";
        $template = \App\Models\Setting::get('whatsapp_template_late', $defaultTemplate);

        return self::processTemplate($template, [
            'student_name' => $studentName,
            'roll_no' => $rollNo,
            'time' => $time,
            'school_name' => $schoolName,
            'gender' => $gender,
        ]);
    }

    /**
     * Generate a fee payment confirmation message.
     */
    public static function getPaymentMessage(string $studentName, $amount, string $period, string $balance, string $schoolName = null, string $link = null): string
    {
        $schoolName = $schoolName ?: \App\Models\Setting::get('institute_name', 'IMCB G-6/2');
        $defaultTemplate = "*Payment Confirmation*\n\nDear Parents,\nWe have received a payment of Rs. {amount} for {student_name} for the period {period}.\nRemaining Balance: Rs. {balance}\n\nView updated receipt: {challan_link}\n\nThank you.\n- {school_name} Administration";
        $template = \App\Models\Setting::get('whatsapp_template_payment', $defaultTemplate);

        $linkStr = $link ? $link : '';

        return str_replace(
            ['{student_name}', '{amount}', '{period}', '{balance}', '{school_name}', '{challan_link}'],
            [$studentName, $amount, $period, $balance, $schoolName, $linkStr],
            $template
        );
    }

    /**
     * Generate a fee reminder message.
     */
    public static function getFeeReminderMessage(string $studentName, $balance, string $period, string $dueDate, string $schoolName = null, string $link = null): string
    {
        $schoolName = $schoolName ?: \App\Models\Setting::get('institute_name', 'IMCB G-6/2');
        $defaultTemplate = "*Fee Reminder*\n\nDear Parents,\nThis is a friendly reminder that a fee balance of Rs. {balance} is pending for {student_name} for the period {period}.\nPlease pay by {due_date} to avoid late charges.\n\nView voucher: {challan_link}\n\n- {school_name} Administration";
        $template = \App\Models\Setting::get('whatsapp_template_reminder', $defaultTemplate);

        $linkStr = $link ? $link : '';

        return str_replace(
            ['{student_name}', '{balance}', '{period}', '{due_date}', '{school_name}', '{challan_link}'],
            [$studentName, $balance, $period, $dueDate, $schoolName, $linkStr],
            $template
        );
    }
}
