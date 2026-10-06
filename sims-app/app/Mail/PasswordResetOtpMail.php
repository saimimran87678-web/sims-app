<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public string $instituteName;
    public string $instituteShortName;
    public ?string $userName;
    public ?string $logoUrl;
    public int $validMinutes;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string|int $otp,
        string $instituteName,
        ?string $userName = null,
        ?string $logoUrl = null,
        int $validMinutes = 15,
        string $instituteShortName = ''
    ) {
        $this->otp = (string) $otp;
        $this->instituteName = $instituteName;
        $this->userName = $userName;
        $this->logoUrl = $logoUrl;
        $this->validMinutes = $validMinutes;
        $this->instituteShortName = $instituteShortName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $fromAddress = config('mail.from.address');
        $from = !empty($fromAddress)
            ? new Address($fromAddress, $this->instituteName)
            : null;

        return new Envelope(
            from: $from,
            subject: "Password Reset Code: {$this->otp} - {$this->instituteName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset-otp',
            with: [
                'otp' => $this->otp,
                'instituteName' => $this->instituteName,
                'instituteShortName' => $this->instituteShortName,
                'userName' => $this->userName,
                'logoUrl' => $this->logoUrl,
                'validMinutes' => $this->validMinutes,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
