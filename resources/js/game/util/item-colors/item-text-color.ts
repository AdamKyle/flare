import { match } from 'ts-pattern';

import { BaseItemDetails } from '../../api-definitions/items/base-item-details';

export const itemTextColor = (item: BaseItemDetails): string => {
  return match(item)
    .with(
      { is_cosmic: true },
      () => 'text-cosmic-colors-700 dark:text-cosmic-colors-600'
    )
    .with({ is_mythic: true }, () => 'text-amber-600 dark:text-amber-500')
    .with({ is_unique: true }, () => 'text-green-700 dark:text-green-600')
    .when(
      (item) => item.holy_stacks_applied > 0,
      () => 'text-sky-700 dark:text-sky-300'
    )
    .with({ affix_count: 1 }, () => 'text-blue-500')
    .with({ affix_count: 2 }, () => 'text-fuchsia-800 dark:text-fuchsia-500')

    .with({ type: 'trinket' }, () => 'text-red-700 dark:text-red-500')
    .with(
      { type: 'artifact' },
      () => 'text-artifact-colors-800 dark:text-artifact-colors-200'
    )
    .with({ type: 'quest' }, () => 'text-marigold-800 dark:text-marigold-200')

    .otherwise(() => 'text-gray-600 dark:text-white');
};
