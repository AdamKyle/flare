<?php

namespace App\Admin\Jobs;

use App\Admin\Mail\GenericMail;
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

    public $service;

    public $for;

    public $skill;

    public $monsterId;

    public $classId;

    public $adminUser;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $for, GameSkill $skill, User $adminUser, int $monsterId = null, int $classId = null)
    {
        $this->for       = $for;
        $this->skill     = $skill;
        $this->monsterId = $monsterId;
        $this->classId   = $classId;
        $this->adminUser = $adminUser;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(AssignSkillService $service)
    {
        try {
            $service->assignSkill($this->for, $this->skill, $this->monsterId, $this->classId);
        } catch (\Exception $e) {
            $message = 'Something went wrong trying to assign the skills: ' . $e->getMessage();
            
            Mail::to($this->adminUser->email)->send(new GenericMail($this->adminUser, $message, 'Failed to assign skill'));
        }
    }
}
