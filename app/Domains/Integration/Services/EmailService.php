<?php

namespace App\Domains\Integration\Services;

use Illuminate\Support\Facades\Mail;

class EmailService
{
    public function send(string $to, string $subject, string $body): void
    {
        Mail::html($body, function ($message) use ($to, $subject) {
            $message->to($to)
                ->subject($subject)
                ->from(
                    config('mail.from.address'),
                    config('mail.from.name'),
                );
        });
    }

    public function sendTemplate(string $to, string $subject, string $template, array $data = []): void
    {
        Mail::send("emails.{$template}", $data, function ($message) use ($to, $subject) {
            $message->to($to)
                ->subject($subject)
                ->from(
                    config('mail.from.address'),
                    config('mail.from.name'),
                );
        });
    }

    public function sendWelcome(string $to, string $name): void
    {
        $this->sendTemplate($to, "مرحباً بك {$name} 🙌", 'welcome', [
            'name' => $name,
            'subject' => "مرحباً بك {$name} 🙌",
        ]);
    }

    public function sendQualificationConfirmation(string $to, string $name): void
    {
        $this->sendTemplate($to, 'تم تسجيل بياناتك ✅', 'qualification', [
            'name' => $name,
            'subject' => 'تم تسجيل بياناتك ✅',
        ]);
    }

    public function sendOffer(string $to, string $name, string $offerDetails): void
    {
        $this->sendTemplate($to, 'عرض خاص لك 🎁', 'offer', [
            'name' => $name,
            'offer' => $offerDetails,
            'subject' => 'عرض خاص لك 🎁',
        ]);
    }
}
