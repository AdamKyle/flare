import { ItemManagementDefinition } from '../../api/definitions/item-detail-definition';
import { ItemCatalogType } from '../../enums/item-catalog-type';

export default interface ItemManagementDetailsProps {
  type: ItemCatalogType;
  management: ItemManagementDefinition;
}
