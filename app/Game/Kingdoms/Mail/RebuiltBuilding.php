<?php

namespace App\Game\Kingdoms\Mail;

use App\Flare\Models\KingdomBuilding;
use Illuminate\Bus\Queueable;
use Asahasrabuddhe\LaravelMJML\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\User;

class RebuiltBuilding extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var User $user
     */
    public $user;

    /**
     * @var KingdomBuilding $building
     */
    public $building;

    /**
     * Create a new message instance.
     *
     * @param User $user
     * @param array $kingdomData
     * @return void
     */
    public function __construct(User $user, KingdomBuilding $building)
    {
        $this->user     = $user;
        $this->building = $building;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(config('mail.username'), 'Planes of Tlessa')
                    ->subject('One of your buildings was rebuilt.')
                    ->mjml('game.core.kingdoms.mail.rebuilt', [
                        'user'     => $this->user,
                        'building' => $this->building,
                    ]);
    }
}
