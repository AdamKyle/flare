import React, { ReactNode, useState } from 'react';

import AdminMapGemRollDetailSidePeekProps from './types/admin-map-gem-roll-detail-side-peek-props';
import RolledGemStats from '../../../../game/reusable-components/gems/components/rolled-gem-stats';
import { useActivateMapGemRoll } from '../../api/hooks/use-activate-map-gem-roll';
import { MAP_GEM_RANGE_DISPLAY_GROUPS } from '../../definitions/map-gem-range-display';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { useSidePeekOptions } from 'ui/side-peek/options/hooks/use-side-peek-options';
import SidePeekOptionDefinition from 'ui/side-peek/options/types/side-peek-option-definition';

const AdminMapGemRollDetailSidePeek = ({
  map_gem_id: mapGemId,
  roll,
  on_activated: onActivated,
}: AdminMapGemRollDetailSidePeekProps): ReactNode => {
  const [isActive, setIsActive] = useState(roll.is_active);
  const {
    activating,
    error,
    activate_roll: activateRoll,
  } = useActivateMapGemRoll();

  const handleActivate = async (): Promise<void> => {
    const activated = await activateRoll(mapGemId, roll.id);

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
        display_groups={MAP_GEM_RANGE_DISPLAY_GROUPS}
      />
    </div>
  );
};

export default AdminMapGemRollDetailSidePeek;
