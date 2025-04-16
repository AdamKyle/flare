import { match } from 'ts-pattern';

import { BaseItemDetails } from '../../../../../api-definitions/items/base-item-details';

export const backpackBaseItemStyles = () => {
  return (
    'border-2 w-full flex items-center p-4 space-x-4 ' +
    'rounded-lg shadow-md focus:outline-none ' +
    'focus:ring-2 my-4'
  );
};

export const backpackFocusRingStyles = (item: BaseItemDetails) => {
  return match(item)
    .with({ is_cosmic: true }, () => 'focus:ring-cosmic-colors-800')
    .with({ is_mythic: true }, () => 'focus:ring-amber-800')
    .with({ is_unique: true }, () => 'focus:ring-green-800')
    .when(
      (item) => item.holy_stacks_applied > 0,
      () => 'focus:ring-sky-800'
    )
    .with({ affix_count: 1 }, () => 'focus:ring-blue-800')
    .with({ affix_count: 2 }, () => 'focus:ring-fuchsia-800')



    .with({ type: 'trinket' }, () => 'focus:ring-red-800')
    .with({ type: 'artifact' }, () => 'focus:ring-artifact-colors-800')
    .with({ type: 'quest' }, () => 'focus:ring-marigold-800')

    .when(
      (item) =>
        item.usable || (item.holy_level ?? 0) > 0 || item.damages_kingdoms,
      () => 'focus:ring-wisp-pink-800'
    )
    .otherwise(() => 'focus:ring-gray-800');
};

export const backpackBorderStyles = (item: BaseItemDetails) => {
  return match(item)
    .with(
      { is_cosmic: true },
      () => 'border-cosmic-colors-800 dark:border-cosmic-colors-500'
    )
    .with({ is_mythic: true }, () => 'border-amber-800 dark:border-amber-500')
    .with({ is_unique: true }, () => 'border-green-800 dark:border-green-500')
    .when(
      (item) => item.holy_stacks_applied > 0,
      () => 'border-sky-800 dark:border-sky-500'
    )
    .with({ affix_count: 1 }, () => 'border-blue-500')
    .with(
      { affix_count: 2 },
      () => 'border-fuchsia-800 dark:border-fuchsia-300'
    )



    .with({ type: 'trinket' }, () => 'border-red-800 dark:border-red-500')
    .with(
      { type: 'artifact' },
      () => 'border-artifact-colors-800 dark:border-artifact-colors-500'
    )
    .with(
      { type: 'quest' },
      () => 'border-marigold-800 dark:border-marigold-500'
    )

    .when(
      (item) =>
        item.usable || (item.holy_level ?? 0) > 0 || item.damages_kingdoms,
      () => 'border-wisp-pink-800 dark:border-wisp-pink-500'
    )
    .otherwise(() => 'border-gray-800 dark:border-gray-500');
};

export const backpackButtonBackground = (item: BaseItemDetails) => {
  return match(item)
    .with(
      { is_cosmic: true },
      () =>
        'bg-cosmic-colors-200 dark:bg-cosmic-colors-100 hover:bg-cosmic-colors-300 dark:hover:bg-cosmic-colors-200'
    )
    .with(
      { is_mythic: true },
      () =>
        'bg-amber-200 dark:bg-amber-100 hover:bg-amber-300 dark:hover:bg-amber-200'
    )
    .with(
      { is_unique: true },
      () =>
        'bg-green-200 dark:bg-green-100 hover:bg-green-300 dark:hover:bg-green-200'
    )
    .when(
      (item) => item.holy_stacks_applied > 0,
      () => 'bg-sky-200 dark:bg-sky-100 hover:bg-sky-300 dark:hover:bg-sky-200'
    )

    .with(
      { affix_count: 1 },
      () =>
        'bg-blue-200 dark:bg-blue-100 hover:bg-blue-300 dark:hover:bg-blue-200'
    )
    .with(
      { affix_count: 2 },
      () =>
        'bg-fuchsia-200 dark:bg-fuchsia-100 hover:bg-fuchsia-300 dark:hover:bg-fuchsia-200'
    )



    .with(
      { type: 'trinket' },
      () => 'bg-red-200 dark:bg-red-100 hover:bg-red-300 dark:hover:bg-red-200'
    )
    .with(
      { type: 'artifact' },
      () =>
        'bg-artifact-colors-200 dark:bg-artifact-colors-100 hover:bg-artifact-colors-300 dark:hover:bg-artifact-colors-200'
    )
    .with(
      { type: 'quest' },
      () =>
        'bg-marigold-200 dark:bg-marigold-100 hover:bg-marigold-300 dark:hover:bg-marigold-200'
    )

    .when(
      (item) =>
        item.usable || (item.holy_level ?? 0) > 0 || item.damages_kingdoms,
      () =>
        'bg-wisp-pink-200 dark:bg-wisp-pink-100 hover:bg-wisp-pink-300 dark:hover:bg-wisp-pink-200'
    )
    .otherwise(
      () =>
        'bg-gray-200 dark:bg-gray-200 hover:bg-gray-300 dark:bg-gray-100 dark:hover:bg-gray-300'
    );
};

export const backpackItemTextColors = (item: BaseItemDetails): string => {
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
    .with({ affix_count: 2 }, () => 'text-fuchsia-800 dark:text-fuchsia-600')



    .with({ type: 'trinket' }, () => 'text-red-700 dark:text-red-500')
    .with(
      { type: 'artifact' },
      () => 'text-artifact-colors-800 dark:text-artifact-colors-200'
    )
    .with({ type: 'quest' }, () => 'text-marigold-800 dark:text-marigold-400')

    .when(
      (item) =>
        item.usable || (item.holy_level ?? 0) > 0 || item.damages_kingdoms,
      () => 'text-wisp-pink-700 dark:text-wisp-pink-600'
    )
    .otherwise(() => 'text-gray-600 dark:text-gray-700');
};
