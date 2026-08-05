import React, { ReactNode } from 'react';

import CraftTypeFiltersProps from './types/craft-type-filters-props';
import { armourTypeOptions, craftTypeOptions } from '../utils/crafting-options';

import Dropdown from 'ui/drop-down/drop-down';

const CraftTypeFilters = ({
  selectedType,
  armourType,
  selectedTypeOption,
  selectedArmourTypeOption,
  onTypeChange,
  onArmourTypeChange,
}: CraftTypeFiltersProps): ReactNode => {
  const renderArmourTypeFieldset = () => {
    if (selectedType !== 'armour') {
      return null;
    }

    return (
      <fieldset>
        <legend
          id="crafting-armour-type-legend"
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Armour Type
        </legend>
        <Dropdown
          aria_labelled_by="crafting-armour-type-legend"
          items={armourTypeOptions}
          on_select={onArmourTypeChange}
          selection_placeholder="Select an armour type"
          pre_selected_item={selectedArmourTypeOption}
          force_clear={armourType === null}
        />
      </fieldset>
    );
  };

  return (
    <>
      <fieldset>
        <legend
          id="crafting-type-legend"
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Craft Type
        </legend>
        <Dropdown
          aria_labelled_by="crafting-type-legend"
          items={craftTypeOptions}
          on_select={onTypeChange}
          selection_placeholder="Select a craft type"
          pre_selected_item={selectedTypeOption}
          force_clear={selectedType === null}
        />
      </fieldset>

      {renderArmourTypeFieldset()}
    </>
  );
};

export default CraftTypeFilters;
