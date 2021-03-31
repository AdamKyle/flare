<?php

namespace App\Flare\Mail;

use Illuminate\Bus\Queueable;
use Asahasrabuddhe\LaravelMJML\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\User;

class TestEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $title;

    /**
     * Create a new message instance.
     *
     * @param User $user
     * @param string $token
     * @return void
     */
    public function __construct($title)
    {
        $this->title = $title;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Test Email')
                    ->mjml('flare.email.sample_email', [
                        'title' => $this->title
                    ]);
    }
}
