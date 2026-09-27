import React, { ReactNode } from 'react';

import BuildingFormFieldsProps from '../../types/building-form-fields-props';
import SelectedUnitRowDefinition from '../../types/selected-unit-row-definition';
import { buildSelectedUnitRows } from '../../utils/build-selected-unit-rows';
import { moveSelectedUnit } from '../../utils/move-selected-unit';
import { parseBuildingDropdownValue } from '../../utils/parse-building-dropdown-value';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import IconButton from 'ui/buttons/icon-button';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import CheckboxField from 'ui/forms/checkbox-field';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';

const BuildingUnitRecruitmentFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: BuildingFormFieldsProps): ReactNode => {
  const availableUnitItems: DropdownItem[] = formOptions.units
    .filter((unit) => !state.unit_ids.includes(unit.id))
    .map((unit) => ({
      label: unit.name,
      value: unit.id,
    }));

  const selectedUnitRows = buildSelectedUnitRows(
    state.unit_ids,
    formOptions.units
  );

  const handleAddUnit = (item: DropdownItem): void => {
    const unitId = parseBuildingDropdownValue(item.value);

    if (unitId === null || state.unit_ids.includes(unitId)) {
      return;
    }

    onChange('unit_ids', [...state.unit_ids, unitId]);
  };

  const handleMoveUnit = (unitId: number, offset: -1 | 1): void => {
    onChange('unit_ids', moveSelectedUnit(state.unit_ids, unitId, offset));
  };

  const handleRemoveUnit = (unitId: number): void => {
    onChange(
      'unit_ids',
      state.unit_ids.filter((selectedUnitId) => selectedUnitId !== unitId)
    );
  };

  const renderSelectedUnit = (row: SelectedUnitRowDefinition): ReactNode => (
    <li
      key={row.unit_id}
      className="flex flex-wrap items-center justify-between gap-2 rounded-md border border-gray-300 p-2 dark:border-gray-700"
    >
      <span className="text-gray-900 dark:text-gray-100">
        {row.position}. {row.name}
      </span>
      <span className="flex gap-2">
        <IconButton
          on_click={() => handleMoveUnit(row.unit_id, -1)}
          variant={ButtonVariant.PRIMARY}
          icon={<i className="fas fa-arrow-up" aria-hidden="true" />}
          aria_label={`Move ${row.name} earlier in the recruitment order`}
          disabled={!row.can_move_up}
        />
        <IconButton
          on_click={() => handleMoveUnit(row.unit_id, 1)}
          variant={ButtonVariant.PRIMARY}
          icon={<i className="fas fa-arrow-down" aria-hidden="true" />}
          aria_label={`Move ${row.name} later in the recruitment order`}
          disabled={!row.can_move_down}
        />
        <IconButton
          on_click={() => handleRemoveUnit(row.unit_id)}
          variant={ButtonVariant.DANGER}
          icon={<i className="fas fa-times" aria-hidden="true" />}
          aria_label={`Remove ${row.name}`}
        />
      </span>
    </li>
  );

  const renderSelectedUnits = (): ReactNode => {
    if (selectedUnitRows.length === 0) {
      return (
        <p className="text-sm text-gray-700 dark:text-gray-300">
          No Units selected.
        </p>
      );
    }

    return (
      <ol
        aria-label="Units to recruit, in recruitment order"
        className="space-y-2"
      >
        {selectedUnitRows.map(renderSelectedUnit)}
      </ol>
    );
  };

  const renderRecruitmentSchedule = (): ReactNode => {
    if (!state.trains_units) {
      return null;
    }

    return (
      <>
        <FieldWrapper
          id="building-units-to-recruit"
          label="Units to Recruit"
          description="Units unlock in the order listed. The first Unit unlocks at level 1 and each following Unit unlocks Units Per Level levels later, unless Unit At Only Level is set."
          error={errors.unit_ids}
        >
          {(describedBy) => (
            <Dropdown
              key={state.unit_ids.length}
              id="building-units-to-recruit"
              aria_label="Add a Unit to recruit"
              aria_described_by={describedBy}
              aria_invalid={!!errors.unit_ids}
              searchable
              items={availableUnitItems}
              on_select={handleAddUnit}
              selection_placeholder="Add a Unit"
            />
          )}
        </FieldWrapper>

        {renderSelectedUnits()}

        <div className="grid gap-4 md:grid-cols-2">
          <NumberField
            id="building-units-per-level"
            label="Units Per Level"
            value={state.units_per_level}
            on_change={(value) => onChange('units_per_level', value)}
            error={errors.units_per_level}
          />
          <NumberField
            id="building-only-at-level"
            label="Unit At Only Level"
            value={state.only_at_level}
            on_change={(value) => onChange('only_at_level', value)}
            error={errors.only_at_level}
          />
        </div>
      </>
    );
  };

  return (
    <div className="space-y-4">
      <CheckboxField
        id="building-trains-units"
        label="Can Train Units"
        checked={state.trains_units}
        on_change={(checked) => onChange('trains_units', checked)}
        error={errors.trains_units}
      />

      {renderRecruitmentSchedule()}
    </div>
  );
};

export default BuildingUnitRecruitmentFields;
