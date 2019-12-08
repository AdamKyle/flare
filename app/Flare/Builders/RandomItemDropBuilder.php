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
        $item = Item::inRandomOrder()->with(['itemAffixes'])->doesntHave('artifactProperty')->get()->first();

        $duplicateItem = $item->replicate();
        $duplicateItem->save();

        if ($item->itemAffixes->isNotEmpty()) {
            foreach ($item->itemAffixes as $itemAffix) {
                $duplicateItem->itemAffixes()->create($itemAffix->getAttributes());
            }
        }

        $duplicateItem->refresh()->load(['itemAffixes']);

        if ($this->shouldHaveArtifactAttached($character)) {
            $artifact            = $this->fetchRandomArtifactProperty();
            $artifact['item_id'] = $item->id;

            $duplicateItem->artifactProperty()->create($artifact);
        }

        if ($this->shouldHaveItemAffix($character)) {
            $affix            = $this->fetchRandomItemAffix();
            $affix['item_id'] = $item->id;

            if ($duplicateItem->itemAffixes->isNotEmpty()) {
                $hasSameAffix = $duplicateItem->itemAffixes->where('type', '=', $affix['type'])->first();

                if (!is_null($hasSameAffix)) {
                    $duplicateItem->delete();

                    return $item;
                } else {
                    $duplicateItem->itemAffixes()->create($affix);
                }
            } else {
                $duplicateItem->itemAffixes()->create($affix);
            }
        } else {
            $duplicateItem->delete();

            return $item;
        }

        $duplicateItem = $this->setItemName($duplicateItem->load(['itemAffixes', 'artifactProperty']));
        $foundItems    = Item::where('name', '=', $duplicateItem->name);

        if ($foundItems->count() > 1) {
            $item = Item::where('name', '=', $duplicateItem->name)->first();

            $duplicateItem->delete();

            return $item;
        }

        return $duplicateItem;
    }

    protected function shouldHaveArtifactAttached(Character $character): bool {
        $lootingChance = $character->skills->where('name', '=', 'Looting')->first()->skill_bonus;

        return rand(1, 100) + $lootingChance > 60;
    }

    protected function shouldHaveItemAffix(Character $character): bool {
        $lootingChance = $character->skills->where('name', '=', 'Looting')->first()->skill_bonus;

        return rand(1, 100) + $lootingChance > 50;
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
