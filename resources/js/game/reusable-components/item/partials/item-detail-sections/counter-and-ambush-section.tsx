import React from 'react';

import { Detail } from '../../../../api-definitions/items/item-comparison-details';
import ItemDetails from '../../../../api-definitions/items/item-details';
import {
  ItemDetailAttributeSectionProps,
  NumericKeys,
} from '../../types/partials/item-detail-sections/item-detail-attribute-section-props';
import { areAttributeValuesEmpty } from '../../utils/are-attribute-values-empty';
import ItemDetailSection from '../item-detail-section';

import Dl from 'ui/dl/dl';
import Separator from 'ui/separator/separator';

const CounterAndAmbushSection = <
  T extends ItemDetails | Detail,
  K extends NumericKeys<T>,
>({
  item,
  attributes,
  is_adjustment,
}: ItemDetailAttributeSectionProps<T, K>) => {
  if (areAttributeValuesEmpty(item, attributes)) {
    return null;
  }

  return (
    <div>
      <h4 className="mb-2 text-sm font-semibold text-danube-600 dark:text-danube-300">
        Counter and Ambush
      </h4>
      <Separator />
      <Dl>
        {attributes.map(({ label, attribute }) => (
          <ItemDetailSection
            key={String(attribute)}
            label={label}
            item_type={item.type}
            value={item[attribute] as number}
            is_adjustment={is_adjustment}
          />
        ))}
      </Dl>
    </div>
  );
};

export default CounterAndAmbushSection;
