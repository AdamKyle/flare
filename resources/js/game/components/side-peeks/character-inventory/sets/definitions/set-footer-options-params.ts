import SetActionAvailabilityDefinition from './set-action-availability-definition';

export default interface SetFooterOptionsParams {
  availability: SetActionAvailabilityDefinition;
  is_equipment_restricted: boolean;
  is_equipping: boolean;
  is_unequipping: boolean;
  is_emptying: boolean;
  on_equip: () => void;
  on_unequip: () => void;
  on_empty: () => void;
}
