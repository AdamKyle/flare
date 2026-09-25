import SetActionAvailabilityDefinition from '../definitions/set-action-availability-definition';
import SetOptionDefinition from '../definitions/set-options-definition';

export const resolveSetActionAvailability = (
  selectedSet: SetOptionDefinition | null
): SetActionAvailabilityDefinition => {
  if (selectedSet === null) {
    return {
      can_equip: false,
      can_unequip: false,
      can_empty: false,
      is_violating_set_rules: false,
    };
  }

  const hasItems = selectedSet.current_slots > 0;
  const isNormalUnequippedSet =
    hasItems && !selectedSet.equipped && !selectedSet.is_batch_crafting_set;
  const passesSetRules = selectedSet.equippable && selectedSet.is_equippable;

  return {
    can_equip: isNormalUnequippedSet && passesSetRules,
    can_unequip: selectedSet.equipped,
    can_empty: hasItems && !selectedSet.equipped && selectedSet.can_empty,
    is_violating_set_rules: isNormalUnequippedSet && !passesSetRules,
  };
};
