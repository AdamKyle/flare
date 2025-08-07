import { Detail } from '../../../../../api-definitions/items/item-comparison-details';
import ItemDetails from '../../../../../api-definitions/items/item-details';

export type NumericKeys<T> = {
  [P in keyof T]: T[P] extends number ? P : never;
}[keyof T];

export interface Attributes<K> {
  label: string;
  expanded_only?: boolean;
  attribute: K;
}

export interface ItemDetailAttributeSectionProps<
  T extends ItemDetails | Detail,
  K extends NumericKeys<T>,
> {
  item: T;
  attributes: Attributes<K>[];
  is_adjustment?: boolean;
  is_expanded?: boolean;
}
