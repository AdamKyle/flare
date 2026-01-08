import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import BaseInventoryItemDefinition from '../../../../../../side-peeks/character-inventory/api-definitions/base-inventory-item-definition';

export interface CharacterEquippedDefinition {
  equipped_items: BaseInventoryItemDefinition[];
  weapon_damage: number;
  spell_damage: number;
  healing_amount: number;
  defence_amount: number;
}

export default interface UseCharacterEquippedApiDefinition {
  data: CharacterEquippedDefinition | null;
  error: AxiosErrorDefinition | null;
  loading: boolean;
}
