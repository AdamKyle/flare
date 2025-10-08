import React, { useState } from 'react';

import EquipComparisonProps from '../../types/partials/equip/equip-comparison-props';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import IconButton from 'ui/buttons/icon-button';

const EquipComparison = ({ comparisonData }: EquipComparisonProps) => {
  const [showAdvanced, setShowAdvanced] = useState(false);

  const handleToggleAdvanced = () => {
    setShowAdvanced((previous) => !previous);
  };

  if (!comparisonData) {
    return (
      <div className="text-center space-y-3">
        There is nothing equipped for this position.
      </div>
    );
  }

  return (
    <div className="space-y-3">
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
      Additional Content Here ....
    </div>
  );
};

export default EquipComparison;
