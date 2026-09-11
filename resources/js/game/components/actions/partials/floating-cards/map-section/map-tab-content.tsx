import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { isEmpty } from 'lodash';
import React, { ReactNode } from 'react';

import GemWorldActions from './gem-world-actions';
import { MapMovementTypes } from './map-movement-types/map-movement-types';
import MapTabContentProps from './types/map-tab-content-props';
import Map from '../../../../map-section/map';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import TimerBar from 'ui/timer-bar/timer-bar';

const MapTabContent = ({
  character_map_position: characterMapPosition,
  can_move: canMove,
  show_timer_bar: showTimerBar,
  length_of_time: lengthOfTime,
  error_message: errorMessage,
  on_close_alert: onCloseAlert,
  on_move: onMove,
  on_teleport: onTeleport,
  is_set_sail_disabled: isSetSailDisabled,
  on_set_sail: onSetSail,
  on_traverse: onTraverse,
  is_conjure_enabled: isConjureEnabled,
  on_conjure: onConjure,
  is_view_location_enabled: isViewLocationEnabled,
  on_view_location: onViewLocation,
  on_open_kingdoms: onOpenKingdoms,
  gem_world_actions_props: gemWorldActionsProps,
}: MapTabContentProps): ReactNode => {
  const renderTimerBar = (): ReactNode => {
    if (!showTimerBar) {
      return null;
    }

    return (
      <TimerBar
        length={lengthOfTime}
        title={'Movement Timeout'}
        additional_css={'my-2'}
      />
    );
  };

  const renderMapError = (): ReactNode => {
    if (isEmpty(errorMessage)) {
      return null;
    }

    return <ApiErrorAlert apiError={errorMessage} on_close={onCloseAlert} />;
  };

  return (
    <>
      <div className="text-center">
        <Map additional_css={'h-[350px] border-2 border-slate-600'} zoom={2} />
      </div>
      {renderTimerBar()}
      {renderMapError()}
      <div className="my-2 p-2">
        Map Position (X/Y): {characterMapPosition.x_position}/
        {characterMapPosition.y_position})
      </div>
      <div className="my-2 flex flex-col justify-center gap-2 p-2 md:flex-row">
        <Button
          on_click={() => onMove(-16, MapMovementTypes.NORTH)}
          label={'North'}
          variant={ButtonVariant.PRIMARY}
          disabled={!canMove}
        />
        <Button
          on_click={() => onMove(16, MapMovementTypes.SOUTH)}
          label={'South'}
          variant={ButtonVariant.PRIMARY}
          disabled={!canMove}
        />
        <Button
          on_click={() => onMove(-16, MapMovementTypes.WEST)}
          label={'West'}
          variant={ButtonVariant.PRIMARY}
          disabled={!canMove}
        />
        <Button
          on_click={() => onMove(16, MapMovementTypes.EAST)}
          label={'East'}
          variant={ButtonVariant.PRIMARY}
          disabled={!canMove}
        />
      </div>

      <div className="my-2 flex flex-col justify-center gap-2 p-2 md:flex-row">
        <Button
          on_click={onTeleport}
          label={'Teleport'}
          variant={ButtonVariant.PRIMARY}
          disabled={!canMove}
        />
        <Button
          on_click={onSetSail}
          label={'Set Sail'}
          variant={ButtonVariant.PRIMARY}
          disabled={isSetSailDisabled}
        />
        <Button
          on_click={onTraverse}
          label={'Traverse'}
          variant={ButtonVariant.PRIMARY}
          disabled={!canMove}
        />
        <Button
          on_click={onConjure}
          label={'Conjure'}
          variant={ButtonVariant.PRIMARY}
          disabled={!isConjureEnabled}
        />
      </div>
      <div className="my-2 w-full p-2">
        <Button
          on_click={onViewLocation}
          label={'View Location'}
          variant={ButtonVariant.SUCCESS}
          additional_css={'w-full'}
          disabled={!isViewLocationEnabled}
        />
      </div>
      <div className="my-2 flex flex-col justify-center gap-2 p-2 md:flex-row">
        <Button
          on_click={onOpenKingdoms}
          label={'My Kingdoms'}
          variant={ButtonVariant.PRIMARY}
        />
      </div>
      <GemWorldActions {...gemWorldActionsProps} />
    </>
  );
};

export default MapTabContent;
