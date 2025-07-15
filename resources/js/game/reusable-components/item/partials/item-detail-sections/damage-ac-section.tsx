import React from 'react';

import { armourPositions } from '../../../../components/character-sheet/partials/character-inventory/enums/inventory-item-types';
import { ItemBaseTypes } from '../../enums/item-base-type';
import DamageAcSectionProps from '../../types/partials/item-detail-sections/damage-ac-section-props';
import { getType } from '../../utils/get-type';
import ItemDetailSection from '../item-detail-section';

import Dl from 'ui/dl/dl';
import Separator from 'ui/separator/separator';

const DamageAcSection = ({ item }: DamageAcSectionProps) => {
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

  return (
    <div>
      <h4 className="mb-2 text-sm font-semibold text-danube-600 dark:text-danube-300">
        Damage / AC
      </h4>
      <Separator />
      <Dl>{renderDamageOrAcValue()}</Dl>
    </div>
  );
};

export default DamageAcSection;
