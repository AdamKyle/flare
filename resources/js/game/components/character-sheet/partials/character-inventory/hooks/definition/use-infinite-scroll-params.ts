import BaseGemDetails from '../../../../../../api-definitions/items/base-gem-details';
import BaseInventoryItemDefinition from '../../api-definitions/base-inventory-item-definition';

export default interface UseInfiniteScrollParams {
  items: BaseInventoryItemDefinition[] | BaseGemDetails[];
  chunkSize: number;
}
