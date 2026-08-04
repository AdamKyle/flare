import TrinketDefinition from '../../api/definitions/trinket-definition';

export default interface TrinketSelectionProps {
  items: TrinketDefinition[];
  selectedItemId: number | null;
  onSelect: (itemId: number) => void;
}
