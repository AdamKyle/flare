import { match } from 'ts-pattern';

import BaseInventoryItemDefinition from '../../../../side-peeks/character-inventory/api-definitions/base-inventory-item-definition';
import {
  defaultPositionImage,
  defaultPositionImageAlt,
  Position,
} from '../enums/equipment-positions';
import { armourPositions } from '../enums/inventory-item-types';
import FetchEquippedImageDefinition from './definitions/fetch-equipped-image-definition';
import { normalItemRecord } from './image-records/normal-item-record';
import { oneEnchantItemRecord } from './image-records/one-enchant-item-record';
import { twoEnchantItemRecord } from './image-records/two-enchant-item-record';
import { uniqueItemRecord } from './image-records/unique-item-record';

/**
 * Fetch the image path of what's currently equipped.
 *
 * @param position
 * @param item
 */
export const fetchEquippedImage = (
  position: Position,
  item?: BaseInventoryItemDefinition
): FetchEquippedImageDefinition => {
  let path = defaultPositionImage[position];
  let itemName = 'Nothing Equipped';
  const altText = defaultPositionImageAlt[position];

  if (!item) {
    return {
      path,
      itemName,
      altText,
    };
  }

  if (armourPositions.includes(item.type)) {
    path = fetchItemImage(item, position) || defaultPositionImage[position];
    itemName = item.name;
  }

  return {
    path,
    itemName,
    altText,
  };
};

/**
 * Fetch the item image.
 *
 * @param item
 * @param position
 */
const fetchItemImage = (
  item: BaseInventoryItemDefinition,
  position: Position
): string | null => {
  return match(item)
    .with({ is_unique: true, affix_count: 2 }, () => {
      const imagePath = uniqueItemRecord[item.type];
      return isPartialPositionRecord(imagePath)
        ? imagePath[position] || null
        : imagePath || null;
    })
    .with({ is_mythical: true, affix_count: 2 }, () => {
      const imagePath = uniqueItemRecord[item.type];
      return isPartialPositionRecord(imagePath)
        ? imagePath[position] || null
        : imagePath || null;
    })
    .with({ is_cosmic: true, affix_count: 2 }, () => {
      const imagePath = uniqueItemRecord[item.type];
      return isPartialPositionRecord(imagePath)
        ? imagePath[position] || null
        : imagePath || null;
    })
    .with({ affix_count: 2 }, () => {
      const imagePath = twoEnchantItemRecord[item.type];
      return isPartialPositionRecord(imagePath)
        ? imagePath[position] || null
        : imagePath || null;
    })
    .with({ affix_count: 1 }, () => {
      const imagePath = oneEnchantItemRecord[item.type];
      return isPartialPositionRecord(imagePath)
        ? imagePath[position] || null
        : imagePath || null;
    })
    .otherwise(() => {
      const imagePath = normalItemRecord[item.type];
      return isPartialPositionRecord(imagePath)
        ? imagePath[position] || null
        : imagePath || null;
    });
};

/**
 * Is the return path a partial record?
 *
 * @param value
 */
const isPartialPositionRecord = (
  value: unknown
): value is Partial<Record<Position, string>> => {
  if (typeof value !== 'object' || value === null || Array.isArray(value)) {
    return false;
  }

  return Object.keys(value).every(
    (key) =>
      Object.values(Position).includes(key as Position) &&
      typeof (value as Record<string, unknown>)[key] === 'string'
  );
};
