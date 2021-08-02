<?php

namespace App\Admin\Jobs;

use App\Flare\Mail\GenericMail;
use App\Admin\Services\AssignSkillService;
use App\Flare\Models\GameSkill;
use App\Flare\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mail;

class AssignSkillsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Who is the skill for?
     *
     * @var String $for
     */
    public $for;

    /**
     * @var GameSkill $skill
     */
    public $skill;

    /**
     * @var int $monsterId | null
     */
    public $monsterId;

    /**
     * @var User $adminUser
     */
    public $adminUser;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $for, GameSkill $skill, User $adminUser, int $monsterId = null)
    {
        $this->for       = $for;
        $this->skill     = $skill;
        $this->monsterId = $monsterId;
        $this->adminUser = $adminUser;
    }

    /**
     * Attempt to assign the skill.
     *
     * If the skill should fail to be asigned to the intended target for any reason, we email
     * the administrator with the error message.
     *
     * @param AssignSkillService $service
     * @return void
     */
    public function handle(AssignSkillService $service)
    {
        try {
            $service->assignSkill($this->for, $this->skill, $this->monsterId);
        } catch (\Exception $e) {
            $message = 'Something went wrong trying to assign the skills: ' . $e->getMessage();

            Mail::to($this->adminUser->email)->send(new GenericMail($this->adminUser, $message, 'Failed to assign skill'));
        }
    }
}
