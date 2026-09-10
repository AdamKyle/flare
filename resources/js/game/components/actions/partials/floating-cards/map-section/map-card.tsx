import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { isEmpty, isNil } from 'lodash';
import React, { ReactNode, useEffect, useState } from 'react';

import { useDirectionallyMoveCharacter } from './hooks/use-directionally-move-character';
import { useFetchMovementTimeoutData } from './hooks/use-fetch-movement-timeout-data';
import { useManageConjureButtonState } from './hooks/use-manage-conjure-button-state';
import { useManageMapMovementErrorState } from './hooks/use-manage-map-movement-error-state';
import { useManageMapSectionVisibility } from './hooks/use-manage-map-section-visibility';
import { useManagePlayerKingdomManagementVisibility } from './hooks/use-manage-player-kingdom-management-visibility';
import { useManageSetSailButtonState } from './hooks/use-manage-set-sail-button-state';
import { useManageViewLocationState } from './hooks/use-manage-view-location-state';
import { MapMovementTypes } from './map-movement-types/map-movement-types';
import GemWorldSourceDefinition from '../../../../../reusable-components/gems/api/definitions/gem-world-source-definition';
import GemWorldEntryDefinition from '../../../../map-section/api/definitions/gem-world-entry-definition';
import { CharacterPosition } from '../../../../map-section/api/hooks/definitions/base-map-api-definition';
import { useExitGemWorld } from '../../../../map-section/api/hooks/use-exit-gem-world';
import { useGemWorldContext } from '../../../../map-section/api/hooks/use-gem-world-context';
import { useEmitCharacterPosition } from '../../../../map-section/hooks/use-emit-character-position';
import { useOpenConjureSidePeek } from '../../../../map-section/hooks/use-open-conjure-side-peek';
import { useOpenGemWorldSidePeek } from '../../../../map-section/hooks/use-open-gem-world-side-peek';
import { useOpenLocationInfoSidePeek } from '../../../../map-section/hooks/use-open-location-info-side-peek';
import { useOpenSetSailSidePeek } from '../../../../map-section/hooks/use-open-set-sail-side-peek';
import { UseOpenTeleportSidePeek } from '../../../../map-section/hooks/use-open-teleport-side-peek';
import { UseOpenTraverseSidePeek } from '../../../../map-section/hooks/use-open-traverse-side-peek';
import Map from '../../../../map-section/map';
import { useEmitMapRefresh } from '../../../../side-peeks/map-actions/traverse/hooks/use-emit-map-refresh';
import FloatingCard from '../../../components/icon-section/floating-card';

import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';
import { GameDataError } from 'game-data/components/game-data-error';
import { useGameData } from 'game-data/hooks/use-game-data';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LoadingButton from 'ui/buttons/loading-button';
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
  const { isConjureEnabled } = useManageConjureButtonState();
  const { gameData } = useGameData();
  const { openTeleport } = UseOpenTeleportSidePeek();
  const { errorMessage, resetErrorMessage } = useManageMapMovementErrorState();
  const { characterPosition } = useEmitCharacterPosition();
  const { isViewLocationEnabled, locationData } = useManageViewLocationState();
  const { openLocationDetails } = useOpenLocationInfoSidePeek({
    characterData: gameData?.character,
  });
  const { openPlayerKingdoms } = useManagePlayerKingdomManagementVisibility();
  const { openTraverse } = UseOpenTraverseSidePeek();
  const { openSetSail } = useOpenSetSailSidePeek();
  const { openConjure } = useOpenConjureSidePeek();
  const { openGemWorld } = useOpenGemWorldSidePeek();
  const { emitShouldRefreshMap } = useEmitMapRefresh();

  const characterId = gameData?.character?.id ?? 0;
  const gemWorldGameMapId = gameData?.character?.game_map_id ?? 0;

  const {
    data: gemWorldStatus,
    loading: gemWorldContextLoading,
    error: gemWorldContextError,
  } = useGemWorldContext({
    character_id: characterId,
    game_map_id: gemWorldGameMapId,
    x: characterPosition.x,
    y: characterPosition.y,
  });
  const {
    loading: exitingGemWorld,
    error: exitGemWorldError,
    action: exitGemWorld,
  } = useExitGemWorld(characterId);

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

  const handleOpenSetSailSidePeek = () => {
    if (!gameData?.character) {
      return;
    }

    openSetSail(gameData.character);
  };

  const handleOpenConjureSidePeek = () => {
    if (!gameData?.character) {
      return;
    }

    openConjure(gameData.character);
  };

  const handleOpenGemWorldEntrySidePeek = () => {
    const entry = gemWorldStatus?.entry;

    if (isNil(entry)) {
      return;
    }

    openGemWorld(characterId, entry.context, true);
  };

  const handleViewCurrentGemEffects = () => {
    const currentContext = gemWorldStatus?.current_context;

    if (isNil(currentContext)) {
      return;
    }

    openGemWorld(characterId, currentContext, false);
  };

  const handleExitGemWorld = async () => {
    const exited = await exitGemWorld();

    if (!exited) {
      return;
    }

    emitShouldRefreshMap(true);
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

  const describeGemSources = (sources: GemWorldSourceDefinition[]): string =>
    sources
      .map(
        (source) =>
          `${source.type === 'map_gem' ? 'Map Gem' : 'Location Gem'}: ${source.profile_name}`
      )
      .join(' · ');

  const renderGemEntrySourceText = (
    entry: GemWorldEntryDefinition
  ): ReactNode => {
    const matchingSource = entry.context.sources.find(
      (source) => source.type === entry.type
    );

    if (!matchingSource) {
      return null;
    }

    return (
      <div className="text-sm text-gray-700 dark:text-gray-300">
        {describeGemSources([matchingSource])}
      </div>
    );
  };

  const renderGemWorldInsideSection = (): ReactNode => {
    const currentContext = gemWorldStatus?.current_context;

    if (isNil(currentContext)) {
      return null;
    }

    return (
      <div className="my-2 flex flex-col gap-2 p-2">
        <div className="text-sm text-gray-700 dark:text-gray-300">
          Gem World: {currentContext.label}
        </div>
        <div className="flex flex-col justify-center gap-2 md:flex-row">
          <Button
            on_click={handleViewCurrentGemEffects}
            label={'View Gem Effects'}
            variant={ButtonVariant.PRIMARY}
          />
          <LoadingButton
            on_click={() => void handleExitGemWorld()}
            label={'Exit Gem World'}
            loading_label={'Exiting Gem World…'}
            variant={ButtonVariant.DANGER}
            is_loading={exitingGemWorld}
            disabled={!canMove}
          />
        </div>
        {!isNil(exitGemWorldError) && (
          <ApiErrorAlert apiError={exitGemWorldError} />
        )}
      </div>
    );
  };

  const renderGemWorldEntrySection = (
    entry: GemWorldEntryDefinition
  ): ReactNode => (
    <div className="my-2 flex flex-col gap-2 p-2">
      {renderGemEntrySourceText(entry)}
      <Button
        on_click={handleOpenGemWorldEntrySidePeek}
        label={entry.label}
        variant={ButtonVariant.PRIMARY}
        additional_css={'w-full'}
      />
    </div>
  );

  const renderGemWorldCurrentEffectsSection = (): ReactNode => {
    const currentContext = gemWorldStatus?.current_context;

    if (isNil(currentContext)) {
      return null;
    }

    return (
      <div className="my-2 flex flex-col gap-2 p-2">
        <div className="text-sm text-gray-700 dark:text-gray-300">
          {describeGemSources(currentContext.sources)}
        </div>
        <Button
          on_click={handleViewCurrentGemEffects}
          label={'View Gem Effects'}
          variant={ButtonVariant.PRIMARY}
          additional_css={'w-full'}
        />
      </div>
    );
  };

  const renderGemWorldSection = (): ReactNode => {
    if (isNil(gemWorldStatus)) {
      return null;
    }

    if (gemWorldStatus.inside_gem_world) {
      return renderGemWorldInsideSection();
    }

    if (!isNil(gemWorldStatus.entry)) {
      return renderGemWorldEntrySection(gemWorldStatus.entry);
    }

    return renderGemWorldCurrentEffectsSection();
  };

  const renderGemWorldContextError = (): ReactNode => {
    if (isNil(gemWorldContextError)) {
      return null;
    }

    return (
      <div className="my-2 p-2">
        <ApiErrorAlert apiError={gemWorldContextError} />
      </div>
    );
  };

  const renderGemWorldLoadingStatus = (): ReactNode => {
    if (!gemWorldContextLoading || !isNil(gemWorldStatus)) {
      return null;
    }

    return (
      <div
        className="my-2 p-2 text-sm text-gray-500 dark:text-gray-400"
        role="status"
      >
        Loading Gem effects…
      </div>
    );
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
      <div className="my-2 flex flex-col justify-center gap-2 p-2 md:flex-row">
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

      <div className="my-2 flex flex-col justify-center gap-2 p-2 md:flex-row">
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
          on_click={handleOpenSetSailSidePeek}
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
          on_click={handleOpenConjureSidePeek}
          label={'Conjure'}
          variant={ButtonVariant.PRIMARY}
          disabled={!isConjureEnabled}
        />
      </div>
      <div className="my-2 w-full p-2">
        <Button
          on_click={handleViewLocationDetails}
          label={'View Location'}
          variant={ButtonVariant.SUCCESS}
          additional_css={'w-full'}
          disabled={!isViewLocationEnabled}
        />
      </div>
      <div className="my-2 flex flex-col justify-center gap-2 p-2 md:flex-row">
        <Button
          on_click={openPlayerKingdoms}
          label={'My Kingdoms'}
          variant={ButtonVariant.PRIMARY}
        />
      </div>
      {renderGemWorldContextError()}
      {renderGemWorldLoadingStatus()}
      {renderGemWorldSection()}
    </FloatingCard>
  );
};

export default MapCard;
