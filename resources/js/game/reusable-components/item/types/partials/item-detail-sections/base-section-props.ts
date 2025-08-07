import { Detail } from '../../../../../api-definitions/items/item-comparison-details';
import ItemDetails from '../../../../../api-definitions/items/item-details';

export default interface BaseSectionProps {
  item: ItemDetails | Detail;
  is_adjustment?: boolean;
}
