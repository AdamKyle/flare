import BuildingRelatedIdentityDefinition from '../api/definitions/building-related-identity-definition';
import SelectedUnitRowDefinition from '../types/selected-unit-row-definition';

export const buildSelectedUnitRows = (
  unitIds: number[],
  units: BuildingRelatedIdentityDefinition[]
): SelectedUnitRowDefinition[] =>
  unitIds.map((unitId, index) => ({
    unit_id: unitId,
    name: units.find((unit) => unit.id === unitId)?.name ?? `Unit #${unitId}`,
    position: index + 1,
    can_move_up: index > 0,
    can_move_down: index < unitIds.length - 1,
  }));
