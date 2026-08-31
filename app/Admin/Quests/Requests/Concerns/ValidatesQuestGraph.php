<?php

namespace App\Admin\Quests\Requests\Concerns;

use App\Admin\Quests\Support\QuestGraphGuard;
use Illuminate\Validation\Validator;

trait ValidatesQuestGraph
{
    /**
     * Reject a parent Quest selection that is the Quest itself or would create a chain cycle.
     *
     * @param  Validator  $validator  Validator instance to add errors to.
     * @param  int|null  $parentQuestId  Proposed `parent_quest_id` value.
     * @param  int|null  $excludeQuestId  Id of the Quest being updated, or null on create.
     */
    private function validateParentCycle(Validator $validator, ?int $parentQuestId, ?int $excludeQuestId): void
    {
        $error = (new QuestGraphGuard)->parentCycleError($parentQuestId, $excludeQuestId);

        if (! is_null($error)) {
            $validator->errors()->add('parent_quest_id', $error);
        }
    }

    /**
     * Reject a required Quest selection that is the Quest itself or would create a required-Quest cycle.
     *
     * @param  Validator  $validator  Validator instance to add errors to.
     * @param  int|null  $requiredQuestId  Proposed `required_quest_id` value.
     * @param  int|null  $excludeQuestId  Id of the Quest being updated, or null on create.
     */
    private function validateRequiredQuestCycle(Validator $validator, ?int $requiredQuestId, ?int $excludeQuestId): void
    {
        $error = (new QuestGraphGuard)->requiredQuestCycleError($requiredQuestId, $excludeQuestId);

        if (! is_null($error)) {
            $validator->errors()->add('required_quest_id', $error);
        }
    }

    /**
     * Reject a required Quest chain that is missing, duplicated, self-referencing, or circular.
     *
     * @param  Validator  $validator  Validator instance to add errors to.
     * @param  array<int, int>|null  $chainIds  Proposed `required_quest_chain` value.
     * @param  int|null  $excludeQuestId  Id of the Quest being updated, or null on create.
     */
    private function validateRequiredQuestChain(Validator $validator, ?array $chainIds, ?int $excludeQuestId): void
    {
        $error = (new QuestGraphGuard)->requiredQuestChainError($chainIds, $excludeQuestId);

        if (! is_null($error)) {
            $validator->errors()->add('required_quest_chain', $error);
        }
    }
}
