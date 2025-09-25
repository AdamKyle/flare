import React from 'react';

import ShopItemBaseView from './shop-item-base-view';
import PrimaryStatBlock from '../../../reusable-components/item/partials/item-view/primary-stat-block';
import ResistanceBlock from '../../../reusable-components/item/partials/item-view/resistance-block';
import StatAttributesBlock from '../../../reusable-components/item/partials/item-view/stat-attributes-block';
import { InventoryItemTypes } from '../../character-sheet/partials/character-inventory/enums/inventory-item-types';
import ShopCardDetailsProps from '../types/shop-card-details-props';

const ShopCardDetails = ({ item }: ShopCardDetailsProps) => {
  const renderStatsOrResistances = () => {
    if (item.type !== InventoryItemTypes.RING) {
      return <StatAttributesBlock item={item} />;
    }

    return <ResistanceBlock item={item} />;
  };

  return (
    <>
      <div className="w-full">
        <div className="mb-4">
          <h2 className="text-xl text-gray-700 dark:text-gray-300 font-semibold break-words">
            {item.name}
          </h2>
          <p className="my-4 text-gray-700 dark:text-gray-300 break-words">
            {item.description}
          </p>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <ShopItemBaseView item={item} />
          <PrimaryStatBlock item={item} />
          {renderStatsOrResistances()}
        </div>
      </div>
    </>
  );
};

export default ShopCardDetails;
