import React, { ReactNode, useState } from 'react';

import AdminLocationGemRollDetailSidePeekProps from './types/admin-location-gem-roll-detail-side-peek-props';
import RolledGemStats from '../../../../game/reusable-components/gems/components/rolled-gem-stats';
import { useActivateLocationGemRoll } from '../../api/hooks/use-activate-location-gem-roll';
import { LOCATION_GEM_RANGE_DISPLAY_GROUPS } from '../../definitions/location-gem-range-display';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { useSidePeekOptions } from 'ui/side-peek/options/hooks/use-side-peek-options';
import SidePeekOptionDefinition from 'ui/side-peek/options/types/side-peek-option-definition';

const AdminLocationGemRollDetailSidePeek = ({
  location_gem_id: locationGemId,
  roll,
  on_activated: onActivated,
}: AdminLocationGemRollDetailSidePeekProps): ReactNode => {
  const [isActive, setIsActive] = useState(roll.is_active);
  const {
    activating,
    error,
    activate_roll: activateRoll,
  } = useActivateLocationGemRoll();

  const handleActivate = async (): Promise<void> => {
    const activated = await activateRoll(locationGemId, roll.id);

    if (!activated) {
      return;
    }

    setIsActive(true);
    onActivated();
  };

  const resolveFooterOptions = (): SidePeekOptionDefinition[] => {
    if (isActive) {
      return [];
    }

    return [
      {
        id: 'make-active',
        label: 'Make Active',
        loading_label: 'Activating…',
        variant: ButtonVariant.PRIMARY,
        loading: activating,
        on_click: () => void handleActivate(),
      },
    ];
  };

  useSidePeekOptions(resolveFooterOptions());

  return (
    <div className="flex h-full min-h-0 flex-col gap-4 overflow-y-auto px-4 py-4 sm:px-5">
      {error && <Alert variant={AlertVariant.DANGER}>{error.message}</Alert>}
      <RolledGemStats
        roll={roll}
        display_groups={LOCATION_GEM_RANGE_DISPLAY_GROUPS}
      />
    </div>
  );
};

export default AdminLocationGemRollDetailSidePeek;
