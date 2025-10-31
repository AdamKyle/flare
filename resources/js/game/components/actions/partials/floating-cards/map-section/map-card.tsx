import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { isEmpty, isNil } from 'lodash';
import React, { useEffect, useState } from 'react';

import { useDirectionallyMoveCharacter } from './hooks/use-directionally-move-character';
import { useFetchMovementTimeoutData } from './hooks/use-fetch-movement-timeout-data';
import { useManageMapMovementErrorState } from './hooks/use-manage-map-movement-error-state';
import { useManageMapSectionVisibility } from './hooks/use-manage-map-section-visibility';
import { useManagePlayerKingdomManagementVisibility } from './hooks/use-manage-player-kingdom-management-visibility';
import { useManageSetSailButtonState } from './hooks/use-manage-set-sail-button-state';
import { useManageViewLocationState } from './hooks/use-manage-view-location-state';
import { MapMovementTypes } from './map-movement-types/map-movement-types';
import { useListenForMapNameChange } from './websockets/hooks/use-listen-for-map-name-change';
import { CharacterPosition } from '../../../../map-section/api/hooks/definitions/base-map-api-definition';
import { useEmitCharacterPosition } from '../../../../map-section/hooks/use-emit-character-position';
import { useOpenLocationInfoSidePeek } from '../../../../map-section/hooks/use-open-location-info-side-peek';
import { UseOpenTeleportSidePeek } from '../../../../map-section/hooks/use-open-teleport-side-peek';
import { UseOpenTraverseSidePeek } from '../../../../map-section/hooks/use-open-traverse-side-peek';
import Map from '../../../../map-section/map';
import FloatingCard from '../../../components/icon-section/floating-card';

import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';
import { GameDataError } from 'game-data/components/game-data-error';
import { useGameData } from 'game-data/hooks/use-game-data';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import TimerBar from 'ui/timer-bar/timer-bar';

const MapCard = () => {
  const [characterData, setCharacterData] =
    useState<CharacterSheetDefinition | null>(null);

  const [characterMapPosition, setCharacterMapPosition] =
    useState<CharacterPosition>({
      x_position: 0,
      y_position: 0,
    });

  const { closeMapCard } = useManageMapSectionVisibility();
  const { moveCharacterDirectionally } = useDirectionallyMoveCharacter();
  const { canMove, showTimerBar, lengthOfTime } = useFetchMovementTimeoutData();
  const { isSetSailEnabled } = useManageSetSailButtonState();
  const { gameData, updateCharacter } = useGameData();
  const { openTeleport } = UseOpenTeleportSidePeek();
  const { errorMessage, resetErrorMessage } = useManageMapMovementErrorState();
  const { characterPosition } = useEmitCharacterPosition();
  const { isViewLocationEnabled, locationData } = useManageViewLocationState();
  const { openLocationDetails } = useOpenLocationInfoSidePeek({
    characterData: gameData?.character,
  });
  useListenForMapNameChange({
    character_data: gameData?.character || null,
    updateCharacterData: updateCharacter,
  });
  const { openPlayerKingdoms } = useManagePlayerKingdomManagementVisibility();
  const { openTraverse } = UseOpenTraverseSidePeek();

  useEffect(() => {
    if (isNil(gameData)) {
      return;
    }

    if (isNil(gameData.character)) {
      return;
    }

    setCharacterData(gameData.character);
  }, [gameData]);

  useEffect(() => {
    if (characterPosition.x === 0 || characterPosition.y === 0) {
      return;
    }

    setCharacterMapPosition({
      x_position: characterPosition.x,
      y_position: characterPosition.y,
    });
  }, [characterPosition]);

  const handleCloseAlert = () => {
    resetErrorMessage();
  };

  const handleViewLocationDetails = () => {
    if (isNil(locationData)) {
      return;
    }

    openLocationDetails(
      locationData.location_id,
      locationData.location_name,
      characterPosition.x,
      characterPosition.y
    );
  };

  const handleOpenTraverseSidePeek = () => {
    if (!gameData?.character) {
      return;
    }

    openTraverse(gameData.character);
  };

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

  const renderMapError = () => {
    if (isEmpty(errorMessage)) {
      return null;
    }

    return (
      <ApiErrorAlert apiError={errorMessage} on_close={handleCloseAlert} />
    );
  };

  const isSetSailDisabled = () => {
    if (!isSetSailEnabled) {
      return true;
    }

    return !canMove;
  };

  if (isNil(characterData)) {
    return (
      <FloatingCard title={'Error Loading Data'} close_action={closeMapCard}>
        <GameDataError />
      </FloatingCard>
    );
  }

  return (
    <FloatingCard
      title={`Map: ${characterData.map_name}`}
      close_action={closeMapCard}
    >
      <div className="text-center">
        <Map additional_css={'h-[350px] border-2 border-slate-600'} zoom={2} />
      </div>
      {renderTimerBar()}
      {renderMapError()}
      <div className="my-2 p-2">
        Map Position (X/Y): {characterMapPosition.x_position}/
        {characterMapPosition.y_position})
      </div>
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
          on_click={() =>
            openTeleport(
              characterData,
              characterPosition.x,
              characterPosition.y
            )
          }
          label={'Teleport'}
          variant={ButtonVariant.PRIMARY}
          disabled={!canMove}
        />
        <Button
          on_click={() => {}}
          label={'Set Sail'}
          variant={ButtonVariant.PRIMARY}
          disabled={isSetSailDisabled()}
        />
        <Button
          on_click={handleOpenTraverseSidePeek}
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
          on_click={handleViewLocationDetails}
          label={'View Location'}
          variant={ButtonVariant.SUCCESS}
          additional_css={'w-full'}
          disabled={!isViewLocationEnabled}
        />
      </div>
      <div className="my-2 p-2 flex flex-col gap-2 md:flex-row justify-center">
        <Button
          on_click={openPlayerKingdoms}
          label={'My Kingdoms'}
          variant={ButtonVariant.PRIMARY}
        />
      </div>
    </FloatingCard>
  );
};

export default MapCard;
