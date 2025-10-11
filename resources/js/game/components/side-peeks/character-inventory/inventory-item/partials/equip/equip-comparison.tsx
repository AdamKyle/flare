import React, { useState } from 'react';

import ItemComparisonColumn from '../../../../../../reusable-components/item/partials/item-comparison/item-comparison-column';
import EquipComparisonProps from '../../types/partials/equip/equip-comparison-props';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import IconButton from 'ui/buttons/icon-button';
import Separator from 'ui/separator/separator';

const EquipComparison = ({
  comparison_data,
  show_advanced_child_under_top,
}: EquipComparisonProps) => {
  const [showAdvanced, setShowAdvanced] = useState(false);

  const handleToggleAdvanced = () => {
    setShowAdvanced((previous) => !previous);
  };

  if (!comparison_data) {
    return (
      <div className="text-center space-y-3">
        There is nothing equipped for this position.
      </div>
    );
  }

  const ShowAdvancedChildrenUnderTop =
    show_advanced_child_under_top && showAdvanced;

  return (
    <div className="space-y-3">
      <Separator />
      <div className="flex items-center justify-end">
        <IconButton
          on_click={handleToggleAdvanced}
          icon={
            <i
              className={`fas ${showAdvanced ? 'fa-eye-slash' : 'fa-eye'}`}
              aria-hidden="true"
            />
          }
          variant={ButtonVariant.PRIMARY}
          label={
            showAdvanced ? 'Hide advanced details' : 'Show advanced details'
          }
          additional_css="px-3"
          aria_label={
            showAdvanced ? 'Hide advanced details' : 'Show advanced details'
          }
        />
      </div>
      <ItemComparisonColumn
        row={comparison_data}
        showAdvanced={showAdvanced}
        showAdvancedChildUnderTop={ShowAdvancedChildrenUnderTop}
        showHeaderSection={false}
      />
    </div>
  );
};

export default EquipComparison;
