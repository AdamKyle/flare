<?php

namespace App\Game\BatchCrafting\Values;

enum BatchCraftingDisposition: string
{
    case KEEP = 'keep';
    case KEEP_HIGHEST = 'keep_highest';
    case SELL = 'sell';
    case DESTROY = 'destroy';
    case LIST = 'list';
    case DISENCHANT = 'disenchant';
    case KEEP_BEST_SELL_REST = 'keep_best_sell_rest';
    case KEEP_BEST_DISENCHANT_REST = 'keep_best_disenchant_rest';
    case KEEP_BEST_DESTROY_REST = 'keep_best_destroy_rest';
    case USE_NOW = 'use_now';

    public function label(): string
    {
        return match ($this) {
            self::KEEP => 'Keep',
            self::KEEP_HIGHEST => 'Keep Highest Level Crafted At The End',
            self::SELL => 'Sell',
            self::DESTROY => 'Destroy',
            self::LIST => 'List',
            self::DISENCHANT => 'Disenchant',
            self::KEEP_BEST_SELL_REST => 'Keep Best and Sell Rest',
            self::KEEP_BEST_DISENCHANT_REST => 'Keep Best and Disenchant Rest',
            self::KEEP_BEST_DESTROY_REST => 'Keep Best and Destroy Rest',
            self::USE_NOW => 'Use Now',
        };
    }

    /**
     * Backend-authoritative disposition matrix. `$progress` supplies the sub-mode
     * (craft_mode/alchemy_mode/enchant_mode/holy_oil_mode) so the same
     * BatchCraftingType can allow different dispositions per mode (Amount/Set/
     * Experience/Event). Holy Oils List/Disenchant additionally require the
     * targeted item(s) to actually have enchants, which depends on selected
     * inventory/set state rather than type/mode alone; that check is enforced
     * separately in BatchCraftingService against the real selected items.
     *
     * KEEP_HIGHEST is legacy only: it is never allowed for new batch starts,
     * even though the enum case, label, and processor handling remain in place
     * so already-running/historical batches created before it was replaced by
     * the explicit Keep Best and Sell/Destroy/Disenchant Rest options keep working.
     */
    public function isAllowedFor(BatchCraftingType $type, array $progress = []): bool
    {
        if ($this === self::KEEP_HIGHEST) {
            return false;
        }

        $keepBestOptions = [self::KEEP_BEST_SELL_REST, self::KEEP_BEST_DESTROY_REST, self::KEEP_BEST_DISENCHANT_REST];

        if ($type === BatchCraftingType::ENCHANT) {
            return $this === self::KEEP;
        }

        if ($type === BatchCraftingType::HOLY_OILS) {
            return in_array($this, [self::KEEP, self::SELL, self::DESTROY, self::LIST, self::DISENCHANT], true);
        }

        $mode = match ($type) {
            BatchCraftingType::CRAFT, BatchCraftingType::CRAFT_AND_ENCHANT => $progress['craft_mode'] ?? 'experience',
            BatchCraftingType::ALCHEMY => $progress['alchemy_mode'] ?? 'experience',
            BatchCraftingType::TRINKETRY => 'experience',
            default => 'experience',
        };

        if ($mode === 'event') {
            return $this === self::KEEP;
        }

        $isExperience = $mode === 'experience';

        if (in_array($this, $keepBestOptions, true) && ! $isExperience) {
            return false;
        }

        if ($this === self::USE_NOW) {
            return $type === BatchCraftingType::ALCHEMY;
        }

        return match ($type) {
            BatchCraftingType::CRAFT => match (true) {
                in_array($this, [self::KEEP_BEST_SELL_REST, self::KEEP_BEST_DESTROY_REST], true) => $isExperience,
                default => in_array($this, [self::KEEP, self::SELL, self::DESTROY], true),
            },
            BatchCraftingType::CRAFT_AND_ENCHANT => in_array($this, [self::KEEP, self::SELL, self::DESTROY, self::LIST, self::DISENCHANT], true)
                || ($isExperience && in_array($this, $keepBestOptions, true)),
            BatchCraftingType::ALCHEMY => in_array($this, [self::KEEP, self::DESTROY, self::LIST], true)
                || ($isExperience && $this === self::KEEP_BEST_DESTROY_REST),
            BatchCraftingType::TRINKETRY => in_array($this, [self::KEEP, self::DESTROY, self::KEEP_BEST_DESTROY_REST], true),
            default => false,
        };
    }
}
