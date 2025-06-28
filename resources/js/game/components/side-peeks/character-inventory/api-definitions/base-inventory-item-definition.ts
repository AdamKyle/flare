import { BaseItemDetails } from '../../../../api-definitions/items/base-item-details';
import { BaseUsableItemDetails } from '../../../../api-definitions/items/base-usable-item-details';
import { InventoryPositionDefinition } from '../../../character-sheet/partials/character-inventory/enums/equipment-positions';
import { InventoryItemTypes } from '../../../character-sheet/partials/character-inventory/enums/inventory-item-types';
import LocationDetailsApi from '../../map-actions/api/definitions/location-details-api';

export default interface BaseInventoryItemDefinition
  extends BaseItemDetails,
    BaseUsableItemDetails {
  item_id: number;
  slot_id: number;
  ac: number;
  attack: number;
  position: InventoryPositionDefinition;
  usable: boolean;
  type: InventoryItemTypes;
  damages_kingdoms: boolean;
  kingdom_damage: number;
  lasts_for: number;
  can_stack: boolean;
  effect: string;
  increase_skill_bonus_by: number | null;
  increase_skill_training_bonus_by: number | null;
  fight_time_out_mod_bonus: number | null;
  move_time_out_mod_bonus: number | null;
  base_ac_mod: number | null;
  base_damage_mod: number | null;
  base_healing_mod: number | null;
  stat_increase: number | null;
  holy_level: number | null;
  drop_location: LocationDetailsApi;
}
