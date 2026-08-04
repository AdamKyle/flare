import AlchemyItemDefinition from '../../api/definitions/alchemy-item-definition';

export default interface AlchemyItemSelectionProps {
  items: AlchemyItemDefinition[];
  selectedItemId: number | null;
  onSelect: (itemId: number) => void;
}
