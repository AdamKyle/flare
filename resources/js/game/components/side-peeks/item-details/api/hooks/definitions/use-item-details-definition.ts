import ItemDetailsResponseDefinition from '../../definitions/item-details-response-definition';

export default interface UseItemDetailsDefinition {
  data: ItemDetailsResponseDefinition | null;
  loading: boolean;
  error: string | null;
}
