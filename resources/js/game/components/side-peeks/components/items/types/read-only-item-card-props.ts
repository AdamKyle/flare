import QuestItemOwnershipState from '../enums/quest-item-ownership-state';
import ReadOnlyItemCardDensity from '../enums/read-only-item-card-density';

export default interface ReadOnlyItemCardProps {
  item_id: number;
  name: string;
  description: string;
  effect: string | null;
  usable: boolean;
  ownership_state?: QuestItemOwnershipState;
  density?: ReadOnlyItemCardDensity;
  on_click: (item_id: number) => void;
}
