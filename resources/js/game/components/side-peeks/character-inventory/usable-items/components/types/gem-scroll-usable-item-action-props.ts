import BaseUsableItemDefinition from '../../../../../../api-definitions/items/usable-item-definitions/base-usable-item-definition';

export default interface GemScrollUsableItemActionProps {
  item: BaseUsableItemDefinition;
  character_id: number;
  on_activated: () => void;
}
