import { CurrencyType } from '../enums/currency-type';
import CurrencyPresentationDefinition from '../types/currency-presentation-definition';

export const CURRENCY_PRESENTATION: Record<
  CurrencyType,
  CurrencyPresentationDefinition
> = {
  [CurrencyType.GOLD]: {
    label: 'Gold',
    icon_class: 'ra ra-gold-bar',
    color_class: 'text-marigold-600 dark:text-marigold-400',
    obtained_from:
      'Earned by killing creatures, exploring and selling items to the shop or the Market.',
    used_for:
      'Spent on shop equipment, Market purchases, crafting and many other costs.',
  },
  [CurrencyType.GOLD_DUST]: {
    label: 'Gold Dust',
    icon_class: 'fas fa-magic',
    color_class: 'text-indigo-500 dark:text-indigo-300',
    obtained_from:
      'Gained by disenchanting enchanted items, the daily Gold Dust Lottery and Slots.',
    used_for: 'Used for conjuring Celestials, quests, gear upgrades and more.',
  },
  [CurrencyType.SHARDS]: {
    label: 'Shards',
    icon_class: 'ra ra-crystals',
    color_class: 'text-glacier-600 dark:text-glacier-400',
    obtained_from:
      'Dropped by Celestials and special locations such as the Gold Mines, and won from Slots.',
    used_for: 'Used in Alchemy and other mid to end game crafting.',
  },
  [CurrencyType.COPPER_COINS]: {
    label: 'Copper Coins',
    icon_class: 'fas fa-coins',
    color_class: 'text-mango-tango-600 dark:text-mango-tango-400',
    obtained_from:
      'Dropped by creatures in Purgatory, and won from Slots once the required quest item is owned.',
    used_for: 'Used for Trinket crafting, quests and reincarnation.',
  },
};
