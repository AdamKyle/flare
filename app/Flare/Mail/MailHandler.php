<?php

namespace App\Flare\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class MailHandler extends Mailable
{
    use Queueable, SerializesModels;

    private string $toEmail;

    private Mailable $mailable;

    public function __construct(string $toEmail, Mailable $mailable)
    {
        $this->to = $toEmail;
        $this->mailable = $mailable;
    }

    /**
     * Build the message.
     */
    public function build(): void
    {
        Mail::to($this->toEmail)->send($this->mailable);
    }
}
