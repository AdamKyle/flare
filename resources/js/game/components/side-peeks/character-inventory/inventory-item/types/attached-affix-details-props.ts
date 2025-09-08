import ItemAffixDefinition from '../../../../../api-definitions/items/equippable-item-definitions/item-affix-definition';

export default interface AttachedAffixDetailsProps {
  affix: ItemAffixDefinition;
  on_close: () => void;
}
