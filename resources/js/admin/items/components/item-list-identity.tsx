import React, { ReactNode } from 'react';

import ItemListIdentityProps from './types/item-list-identity-props';
import ItemDefinition from '../api/definitions/item-definition';
import { ITEM_SPECIALTY_TYPE_LABELS } from '../enums/item-specialty-type';

/**
 * Item list identity presentation: the Item name plus, when the Item has a
 * `specialty_type`, a secondary "Specialty: <label>" line beneath it. Used
 * as the identity column value across every Item list profile so specialty
 * equipment (Delusional Silver, Corrupted Ice, Faithless Plate, etc.) is
 * recognizable directly from ordinary Weapons/Armour rows, not only after
 * opening the Item. Root is a `<span>` since `DataTable` already wraps the
 * identity column value in its own `<span>`.
 */
const ItemListIdentity = ({ item }: ItemListIdentityProps): ReactNode => (
  <span>
    <span className="block">{item.name}</span>
    {item.specialty_type !== null && (
      <span className="text-glacier-600 dark:text-glacier-300 block text-xs font-medium">
        Specialty: {ITEM_SPECIALTY_TYPE_LABELS[item.specialty_type]}
      </span>
    )}
  </span>
);

export const renderItemListIdentity = (item: ItemDefinition): ReactNode => (
  <ItemListIdentity item={item} />
);

export default ItemListIdentity;
