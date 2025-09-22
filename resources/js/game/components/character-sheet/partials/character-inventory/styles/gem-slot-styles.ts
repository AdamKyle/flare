import { match } from 'ts-pattern';

import BaseGemDetails from '../../../../../api-definitions/items/base-gem-details';

export const gemSlotFocusRingStyles = (gem: BaseGemDetails): string => {
  return match(gem)
    .with({ tier: 2 }, () => 'focus:ring-green-800')
    .with({ tier: 3 }, () => 'focus:ring-amber-800')
    .with({ tier: 4 }, () => 'focus:ring-marigold-800')
    .otherwise(() => 'focus:ring-gray-800');
};

export const gemSlotBorderStyles = (gem: BaseGemDetails): string => {
  return match(gem)
    .with({ tier: 2 }, () => 'border-green-800 dark:border-green-500')
    .with({ tier: 3 }, () => 'border-amber-800 dark:border-amber-500')
    .with({ tier: 4 }, () => 'border-marigold-800 dark:border-marigold-500')
    .otherwise(() => 'border-gray-800 dark:border-gray-500');
};

export const gemSlotButtonBackgroundColor = (gem: BaseGemDetails): string => {
  return match(gem)
    .with(
      { tier: 2 },
      () =>
        'bg-green-200 dark:bg-green-100 hover:bg-green-300 dark:hover:bg-green-200'
    )
    .with(
      { tier: 3 },
      () =>
        'bg-amber-200 dark:bg-amber-100 hover:bg-amber-300 dark:hover:bg-amber-200'
    )
    .with(
      { tier: 4 },
      () =>
        'bg-marigold-200 dark:bg-marigold-100 hover:bg-marigold-300 dark:hover:bg-marigold-200'
    )
    .otherwise(
      () =>
        'bg-gray-200 dark:bg-gray-200 hover:bg-gray-300 dark:bg-gray-100 dark:hover:bg-gray-300'
    );
};

export const gemSlotTextColor = (gem: BaseGemDetails): string => {
  return match(gem)
    .with({ tier: 2 }, () => 'text-green-700 dark:text-green-600')
    .with({ tier: 3 }, () => 'text-amber-600 dark:text-amber-500')
    .with({ tier: 4 }, () => 'text-marigold-800 dark:text-marigold-400')
    .otherwise(() => 'text-gray-600 dark:text-gray-700');
};

export const getGemSlotTitleTextColor = (gem: BaseGemDetails): string => {
  return match(gem)
    .with({ tier: 2 }, () => 'text-green-700 dark:text-green-600')
    .with({ tier: 3 }, () => 'text-amber-600 dark:text-amber-500')
    .with({ tier: 4 }, () => 'text-marigold-800 dark:text-marigold-400')
    .otherwise(() => 'text-gray-600 dark:text-gray-300');
};
