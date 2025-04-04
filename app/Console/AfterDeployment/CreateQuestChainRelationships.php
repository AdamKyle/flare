<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Quest;
use Illuminate\Console\Command;

class CreateQuestChainRelationships extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:quest-chain-relationships';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates the "required quest chain" relationships for each parent quest that depends on a quest chain before completion';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $questChainsRequiredToStart = $this->questRelationships();

        foreach ($questChainsRequiredToStart as $questChain) {
            $questId = $questChain['parent_id'];
            $questIds = $questChain['required_quest_ids'];

            Quest::find($questId)->update([
                'required_quest_chain' => $questIds
            ]);
        }
    }

    private function questRelationships(): array {
        return [
            [
                'parent_id' => 134,
                'required_quest_ids' => [
                    144, 145, 146, 147, 148
                ],
            ],
            [
                'parent_id' => 149,
                'required_quest_ids' => [
                    134, 135, 136, 137, 138
                ]
            ],
            [
                'parent_id' => 154,
                'required_quest_ids' => [
                    150, 151, 152, 153
                ]
            ],
            [
                'parent_id' => 159,
                'required_quest_ids' => [
                    154, 155, 156, 157, 158
                ]
            ],
            [
                'parent_id' => 170,
                'required_quest_ids' => [
                    164, 165, 166, 167, 168, 169
                ]
            ],
            [
                'parent_id' => 176,
                'required_quest_ids' => [
                    171, 172, 173, 174, 175
                ]
            ],
            [
                'parent_id' => 176,
                'required_quest_ids' => [
                    171, 172, 173, 174, 175
                ]
            ],
            [
                'parent_id' => 180,
                'required_quest_ids' => [
                    176, 177, 178, 179
                ]
            ],
            [
                'parent_id' => 184,
                'required_quest_ids' => [
                    176, 177, 178, 179
                ]
            ],
            [
                'parent_id' => 190,
                'required_quest_ids' => [
                    160, 161, 162, 163
                ]
            ],
            [
                'parent_id' => 191,
                'required_quest_ids' => [
                   184, 184, 185, 186, 187, 188, 189
                ]
            ],
            [
                'parent_id' => 202,
                'required_quest_ids' => [
                    194, 195, 196, 197
                ]
            ],
            [
                'parent_id' => 206,
                'required_quest_ids' => [
                    194, 195, 196, 197
                ]
            ],
            [
                'parent_id' => 252,
                'required_quest_ids' => [
                    244, 245, 246, 247, 248
                ]
            ],
        ];
    }
}
