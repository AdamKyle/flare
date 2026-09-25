import { EquippableItemDetailsDefinition } from '../../../../../../api-definitions/items/equippable-item-definitions/equippable-item-details-definition';

export default interface ItemSkillTreeManagerProps {
  character_id: number;
  item: EquippableItemDetailsDefinition;
  refetch: () => Promise<void>;
}
