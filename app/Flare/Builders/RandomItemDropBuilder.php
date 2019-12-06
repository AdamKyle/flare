<?php

namespace App\Flare\Builders;

use App\Flare\Models\Item;
use App\Flare\Models\Character;

class RandomItemDropBuilder {

    private $artifactProperties;

    private $itemAffixes;

    public function setArtifactProperties(array $artifactProperties): RandomItemDropBuilder {
        $this->artifactProperties = $artifactProperties;

        return $this;
    }

    public function setItemAffixes(array $itemAffixes): RandomItemDropBuilder {
        $this->itemAffixes = $itemAffixes;

        return $this;
    }

    public function generateItem(Character $character): Item {
        $item = Item::inRandomOrder()->whereNull('artifact_property_id')->first();

        $duplicateItem = $item->replicate();

        if (!is_null($item->itemAffix)) {
            $duplicateItem->itemAffix = $item->itemAffix->replicate();
            $duplicateItem->itemAffix->save();
        }

        $duplicateItem->save();

        if ($this->shouldHaveArtifactAttached($character)) {
            $artifact            = $this->fetchRandomArtifactProperty();
            $artifact['item_id'] = $item->id;

            $duplicateItem->artifactProperty()->create($artifact);
        }

        if ($this->shouldHaveItemAffix($character)) {
            $affix            = $this->fetchRandomItemAffix();
            $affix['item_id'] = $item->id;

            if ($duplicateItem->itemAffixes->isNotEmpty()) {
                $types = $duplicateItem->itemAffixes->filter(function($itemAffix) use ($affix) {
                    return $itemAffix->type === $affix['type'];
                })->all();

                if ($types->isEmpty()) {
                    $duplicateItem->itemAffixes()->create($affix);
                } else {
                    $duplicateItem->artifactProperty->delete();
                }
            } else {
                $duplicateItem->itemAffixes()->create($affix);
            }
        }

        if (is_null($duplicateItem->artifactProperty) && $duplicateItem->itemAffixes->isEmpty()) {
            $duplicateItem->delete();

            return $item;
        }

        $duplicateItem = $this->setItemName($duplicateItem->load(['itemAffixes', 'artifactProperty']));

        return $duplicateItem;
    }

    protected function shouldHaveArtifactAttached(Character $character): bool {
        $lootingChance = $character->skills->where('name', '=', 'Looting')->first()->skill_bonus;

        return rand(0, 100) + $lootingChance > 1; //70;
    }

    protected function shouldHaveItemAffix(Character $character): bool {
        $lootingChance = $character->skills->where('name', '=', 'Looting')->first()->skill_bonus;

        return rand(0, 100) + $lootingChance > 1; //60;
    }

    protected function fetchRandomArtifactProperty() {
        return $this->artifactProperties[rand(0, count($this->artifactProperties) - 1)];
    }

    protected function fetchRandomItemAffix() {
        return $this->itemAffixes[rand(0, count($this->itemAffixes) - 1)];
    }

    private function setItemName(Item $item): Item {
        $name    = $item->name;
        $affixes = $item->itemAffixes;

        if ($affixes->isNotEmpty()) {
            foreach($affixes as $affix) {
                if ($affix->type === 'suffix') {
                    $name = $name . ' *' . $affix->name . '*';
                }

                if ($affix->type === 'prefix') {
                    $name = '*'.$affix->name . '* ' . $name;
                }
            }
        }

        $item->name = $name;
        $item->save();

        return $item;
    }
}
