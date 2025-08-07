import { isNil } from 'lodash';

import { Attributes } from '../types/partials/item-detail-sections/item-detail-attribute-section-props';

export const areAttributeValuesEmpty = <T, K extends keyof T>(
  item: T,
  attributes: Attributes<K>[]
): boolean => {
  return attributes
    .map(
      ({ attribute }) => item[attribute] as unknown as number | null | undefined
    )
    .every((v) => isNil(v) || v === 0);
};
