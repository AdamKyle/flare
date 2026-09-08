import React, { ReactNode } from 'react';

import ItemListIdentityProps from './types/item-list-identity-props';
import ItemDefinition from '../api/definitions/item-definition';
import { ITEM_SPECIALTY_TYPE_LABELS } from '../enums/item-specialty-type';

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
