<?php

namespace App\Flare\View\Livewire\Admin\Items\Validators;

use App\Flare\Models\Item;
use Livewire\Component;

class ItemValidator {

    public function validate(Component $component, Item $item) {
        $isValid = true;

        if (!is_null($item->can_craft)) {
            if ($item->can_craft) {
                if (is_null($item->crafting_type)) {
                    $component->addError('crafting_type', 'Cannot be empty when you said this item is craftable.');
                    $isValid = false;
                }

                if (is_null($item->skill_level_required)) {
                    $component->addError('skill_level_required', 'Must have a skill level required to craft.');
                    $isValid = false;
                }

                if (is_null($item->skill_level_trivial)) {
                    $component->addError('skill_level_trivial', 'Must have a skill trivial level.');
                    $isValid = false;
                }
            }
        }

        if (!is_null($item->skill_name)) {
            if (is_null($item->skill_training_bonus)) {
                $component->addError('skill_training_bonus', 'You cannot say this item affects skill training and not say by how much.');
                $isValid = false;
            }
        }

        if ($item->type !== 'quest' && is_null($item->cost)) {
            $component->addError('item.cost', 'How much does this item cost?');
            $isValid = false;
        }

        if ($item->usable) {
            if (is_null($item->lasts_for)) {
                if (!$item->damages_kingdoms) {
                    $component->addError('item.lasts_for', 'How long does the effect last?');

                    $isValid = false;
                }
            }

            if (is_null($item->damages_kingdoms) && empty($item->stat_increase) && is_null($item->affects_skill_type)) {
                $component->showUsabillityError = true;

                $isValid = false;
            }

            if (!is_null($item->damages_kingdoms) && is_null($item->kingdom_damage)) {
                if ($item->damages_kingdoms) {
                    $component->addError('item.kingdom_damage', 'How much damage does this do to kingdoms?');

                    $isValid = false;
                }
            }

            if (!empty($item->stat_increase) && is_null($item->increase_stat_by)) {
                $component->addError('item.increase_stat_by', 'How much does this increase the selected stat by?');

                $isValid = false;
            }

            if (!empty($item->affects_skill_type)) {

                if (is_null($item->increase_skill_bonus_by) && is_null($item->increase_skill_training_bonus_by)) {
                    $component->affectsSkillError = true;

                    $isValid = false;
                }

            }
        }

        return $isValid;
    }
}
