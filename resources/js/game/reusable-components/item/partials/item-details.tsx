import React from 'react';

import ItemDetailSection from './item-detail-section';
import { armourPositions } from '../../../components/character-sheet/partials/character-inventory/enums/inventory-item-types';
import { ItemBaseTypes } from '../enums/item-base-type';
import ItemDetailsProps from '../types/partials/item-details-props';
import { getType } from '../utils/get-type';
import { getBaseItemStats } from '../utils/item-stats';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dl from 'ui/dl/dl';
import Separator from 'ui/separator/separator';

const ItemDetails = ({ item, show_advanced_button }: ItemDetailsProps) => {
  const renderDamageOrAcValue = () => {
    const type = getType(item, armourPositions);

    if (type === ItemBaseTypes.Armour) {
      return (
        <ItemDetailSection
          label={'AC'}
          item_type={item.type}
          value={item.base_ac}
        />
      );
    }

    return (
      <ItemDetailSection
        label={'Damage'}
        item_type={item.type}
        value={item.base_damage}
      />
    );
  };

  const renderAdvancedButton = () => {
    if (!show_advanced_button) {
      return null;
    }

    return (
      <div className="mt-6">
        <Button
          on_click={() => {}}
          label="Advanced Details"
          variant={ButtonVariant.PRIMARY}
        />
      </div>
    );
  };

  return (
    <>
      <div>
        <h4 className="mb-2 text-sm font-semibold text-danube-600 dark:text-danube-300">
          Stats
        </h4>
        <Separator />
        <Dl>
          {getBaseItemStats(item).map((entry, index) => (
            <ItemDetailSection
              item_type={item.type}
              label={entry.label}
              value={entry.value}
              is_percent={entry.isPercent}
              key={`${item.id}-${index}`}
            />
          ))}
        </Dl>
      </div>

      <div>
        <h4 className="mb-2 text-sm font-semibold text-danube-600 dark:text-danube-300">
          Damage / AC
        </h4>
        <Separator />
        <Dl>{renderDamageOrAcValue()}</Dl>
      </div>

      {renderAdvancedButton()}
    </>
  );
};

export default ItemDetails;
