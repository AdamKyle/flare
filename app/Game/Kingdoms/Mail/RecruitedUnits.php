<?php

namespace App\Game\Kingdoms\Mail;

use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use Illuminate\Bus\Queueable;
use Asahasrabuddhe\LaravelMJML\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\User;

class RecruitedUnits extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var User $user
     */
    public $user;

    /**
     * @var Kingdom $kingdom
     */
    public $kingdom;

    /**
     * @var GameUnit $unit
     */
    public $unit;

    /**
     * Create a new message instance.
     *
     * @param User $user
     * @param array $kingdomData
     * @return void
     */
    public function __construct(User $user, GameUnit $unit, Kingdom $kingdom, int $amount)
    {
        $this->user    = $user;
        $this->unit    = $unit;
        $this->kingdom = $kingdom;
        $this->amount  = $amount;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(config('mail.username'), 'Planes of Tlessa')
                    ->subject('Upgraded Building')
                    ->mjml('game.core.kingdoms.mail.units_recruited', [
                        'user'    => $this->user,
                        'unit'    => $this->unit,
                        'kingdom' => $this->kingdom,
                        'amount'  => $this->amount,
                    ]);
    }
}
