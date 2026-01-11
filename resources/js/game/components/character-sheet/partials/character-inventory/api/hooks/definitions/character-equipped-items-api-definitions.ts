import BaseInventoryItemDefinition from '../../../../../../side-peeks/character-inventory/api-definitions/base-inventory-item-definition';

export default interface CharacterInventoryItemDetails {
  equipped: {
    data: BaseInventoryItemDefinition[];
  };
  weapon_damage: number;
  spell_damage: number;
  healing_amount: number;
  defence_amount: number;
  set_name: string | null;
}
