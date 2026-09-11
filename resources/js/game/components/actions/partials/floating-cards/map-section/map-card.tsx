import { isNil } from 'lodash';
import React, { useEffect, useState } from 'react';

import { useDirectionallyMoveCharacter } from './hooks/use-directionally-move-character';
import { useFetchMovementTimeoutData } from './hooks/use-fetch-movement-timeout-data';
import { useManageConjureButtonState } from './hooks/use-manage-conjure-button-state';
import { useManageMapMovementErrorState } from './hooks/use-manage-map-movement-error-state';
import { useManageMapSectionVisibility } from './hooks/use-manage-map-section-visibility';
import { useManagePlayerKingdomManagementVisibility } from './hooks/use-manage-player-kingdom-management-visibility';
import { useManageSetSailButtonState } from './hooks/use-manage-set-sail-button-state';
import { useManageViewLocationState } from './hooks/use-manage-view-location-state';
import MapCardTabs from './map-card-tabs';
import { MapMovementTypes } from './map-movement-types/map-movement-types';
import MapTabContent from './map-tab-content';
import { CharacterPosition } from '../../../../map-section/api/hooks/definitions/base-map-api-definition';
import { useExitGemWorld } from '../../../../map-section/api/hooks/use-exit-gem-world';
import { useGemWorldContext } from '../../../../map-section/api/hooks/use-gem-world-context';
import { useEmitCharacterPosition } from '../../../../map-section/hooks/use-emit-character-position';
import { useOpenAllActiveGemScrollsSidePeek } from '../../../../map-section/hooks/use-open-all-active-gem-scrolls-side-peek';
import { useOpenConjureSidePeek } from '../../../../map-section/hooks/use-open-conjure-side-peek';
import { useOpenGemProgressHistorySidePeek } from '../../../../map-section/hooks/use-open-gem-progress-history-side-peek';
import { useOpenGemWorldIntroductionSidePeek } from '../../../../map-section/hooks/use-open-gem-world-introduction-side-peek';
import { useOpenGemWorldSidePeek } from '../../../../map-section/hooks/use-open-gem-world-side-peek';
import { useOpenLocationInfoSidePeek } from '../../../../map-section/hooks/use-open-location-info-side-peek';
import { useOpenSetSailSidePeek } from '../../../../map-section/hooks/use-open-set-sail-side-peek';
import { UseOpenTeleportSidePeek } from '../../../../map-section/hooks/use-open-teleport-side-peek';
import { UseOpenTraverseSidePeek } from '../../../../map-section/hooks/use-open-traverse-side-peek';
import { useEmitMapRefresh } from '../../../../side-peeks/map-actions/traverse/hooks/use-emit-map-refresh';
import FloatingCard from '../../../components/icon-section/floating-card';

import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';
import { GameDataError } from 'game-data/components/game-data-error';
import { useGameData } from 'game-data/hooks/use-game-data';

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
  const { openGemWorldIntroduction } = useOpenGemWorldIntroductionSidePeek();
  const { openGemProgressHistory } = useOpenGemProgressHistorySidePeek();
  const { openAllActiveGemScrolls } = useOpenAllActiveGemScrollsSidePeek();
  const { emitShouldRefreshMap } = useEmitMapRefresh();

  const characterId = gameData?.character?.id ?? 0;
  const gemWorldGameMapId = gameData?.character?.game_map_id ?? 0;
  const gemWorldIntroductionAcknowledgedAt =
    gameData?.character?.gem_world_introduction_acknowledged_at ?? null;

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

  const handleViewLocationDetails = (): void => {
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

  const handleOpenGemWorldEntrySidePeek = (): void => {
    const entry = gemWorldStatus?.entry;

    if (isNil(entry)) {
      return;
    }

    if (isNil(gemWorldIntroductionAcknowledgedAt)) {
      openGemWorldIntroduction(characterId, entry.context, true);

      return;
    }

    openGemWorld(characterId, entry.context, true);
  };

  const handleViewCurrentGemEffects = (): void => {
    const currentContext = gemWorldStatus?.current_context;

    if (isNil(currentContext)) {
      return;
    }

    openGemWorld(characterId, currentContext, false);
  };

  const handleExitGemWorld = async (): Promise<void> => {
    const exited = await exitGemWorld();

    if (!exited) {
      return;
    }

    emitShouldRefreshMap(true);
  };

  const isSetSailDisabled = !isSetSailEnabled || !canMove;
  const insideGemWorld = gemWorldStatus?.inside_gem_world ?? false;

  if (isNil(characterData)) {
    return (
      <FloatingCard title={'Error Loading Data'} close_action={closeMapCard}>
        <GameDataError />
      </FloatingCard>
    );
  }

  const mapTabContentProps = {
    character_map_position: characterMapPosition,
    can_move: canMove,
    show_timer_bar: showTimerBar,
    length_of_time: lengthOfTime,
    error_message: errorMessage,
    on_close_alert: resetErrorMessage,
    on_move: (amount: number, direction: MapMovementTypes) =>
      moveCharacterDirectionally(amount, direction),
    on_teleport: () =>
      openTeleport(characterData, characterPosition.x, characterPosition.y),
    is_set_sail_disabled: isSetSailDisabled,
    on_set_sail: () => {
      if (gameData?.character) {
        openSetSail(gameData.character);
      }
    },
    on_traverse: () => {
      if (gameData?.character) {
        openTraverse(gameData.character);
      }
    },
    is_conjure_enabled: isConjureEnabled,
    on_conjure: () => {
      if (gameData?.character) {
        openConjure(gameData.character);
      }
    },
    is_view_location_enabled: isViewLocationEnabled,
    on_view_location: handleViewLocationDetails,
    on_open_kingdoms: openPlayerKingdoms,
    gem_world_actions_props: {
      status: gemWorldStatus,
      loading: gemWorldContextLoading,
      context_error: gemWorldContextError,
      can_move: canMove,
      exiting: exitingGemWorld,
      exit_error: exitGemWorldError,
      on_enter: handleOpenGemWorldEntrySidePeek,
      on_view_effects: handleViewCurrentGemEffects,
      on_exit: () => void handleExitGemWorld(),
      on_open_gem_progress_history: () => openGemProgressHistory(characterId),
      on_open_all_active_gem_scrolls: () =>
        openAllActiveGemScrolls(characterId),
    },
  };

  return (
    <FloatingCard
      title={`Map: ${characterData.map_name}`}
      close_action={closeMapCard}
    >
      {insideGemWorld ? (
        <MapCardTabs
          character_id={characterId}
          map_tab_content_props={mapTabContentProps}
        />
      ) : (
        <MapTabContent {...mapTabContentProps} />
      )}
    </FloatingCard>
  );
};

export default MapCard;
