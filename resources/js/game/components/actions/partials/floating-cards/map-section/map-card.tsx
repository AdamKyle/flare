import React from 'react';

import { useDirectionallyMoveCharacter } from './hooks/use-directionally-move-character';
import { useFetchMovementTimeoutData } from './hooks/use-fetch-movement-timeout-data';
import { useManageMapSectionVisibility } from './hooks/use-manage-map-section-visibility';
import { MapMovementTypes } from './map-movement-types/map-movement-types';
import { useToggleFullMapVisibility } from '../../../../map-section/hooks/use-toggle-full-map-visibility';
import Map from '../../../../map-section/map';
import FloatingCard from '../../../components/icon-section/floating-card';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import TimerBar from 'ui/timer-bar/timer-bar';

const MapCard = () => {
  const { closeMapCard } = useManageMapSectionVisibility();
  const { openFullMap } = useToggleFullMapVisibility();
  const { moveCharacterDirectionally } = useDirectionallyMoveCharacter();
  const { canMove, showTimerBar, lengthOfTime } = useFetchMovementTimeoutData();

  const renderTimerBar = () => {
    if (!showTimerBar) {
      return;
    }

    return (
      <TimerBar
        length={lengthOfTime}
        title={'Movement Timeout'}
        additional_css={'my-2'}
      />
    );
  };

  return (
    <FloatingCard title={'Map: Surface'} close_action={closeMapCard}>
      <div className="text-center">
        <Map additional_css={'h-[350px] border-2 border-slate-600'} zoom={2} />
      </div>
      {renderTimerBar()}
      <div className="my-2 p-2 flex flex-col gap-2 md:flex-row justify-center">
        <Button
          on_click={() =>
            moveCharacterDirectionally(-16, MapMovementTypes.NORTH)
          }
          label={'North'}
          variant={ButtonVariant.PRIMARY}
          disabled={!canMove}
        />
        <Button
          on_click={() =>
            moveCharacterDirectionally(16, MapMovementTypes.SOUTH)
          }
          label={'South'}
          variant={ButtonVariant.PRIMARY}
          disabled={!canMove}
        />
        <Button
          on_click={() =>
            moveCharacterDirectionally(-16, MapMovementTypes.WEST)
          }
          label={'West'}
          variant={ButtonVariant.PRIMARY}
          disabled={!canMove}
        />
        <Button
          on_click={() => moveCharacterDirectionally(16, MapMovementTypes.EAST)}
          label={'East'}
          variant={ButtonVariant.PRIMARY}
          disabled={!canMove}
        />
      </div>

      <div className="my-2 p-2 flex flex-col gap-2 md:flex-row justify-center">
        <Button
          on_click={() => {}}
          label={'Teleport'}
          variant={ButtonVariant.PRIMARY}
          disabled={!canMove}
        />
        <Button
          on_click={() => {}}
          label={'Set Sail'}
          variant={ButtonVariant.PRIMARY}
          disabled={!canMove}
        />
        <Button
          on_click={() => {}}
          label={'Traverse'}
          variant={ButtonVariant.PRIMARY}
          disabled={!canMove}
        />
        <Button
          on_click={() => {}}
          label={'Conjure'}
          variant={ButtonVariant.PRIMARY}
        />
      </div>
      <div className="my-2 p-2 w-full">
        <Button
          on_click={openFullMap}
          label={'View Full Map'}
          variant={ButtonVariant.SUCCESS}
          additional_css={'w-full'}
        />
      </div>
    </FloatingCard>
  );
};

export default MapCard;
