import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import PlayerGameMapGemSection from './components/player-game-map-gem-section';
import { PlayerGameMapGemDetail } from './enums/player-game-map-gem-detail';
import PlayerGameMapDetailSidePeekProps from './types/player-game-map-detail-side-peek-props';
import { usePlayerGameMapDetail } from '../../../reusable-components/game-map/api/hooks/use-player-game-map-detail';
import GameMapDetail from '../../../reusable-components/game-map/components/game-map-detail';
import GemContextEffectsDetail from '../../../reusable-components/gems/components/gem-context-effects-detail';
import GemContextProfileDetail from '../../../reusable-components/gems/components/gem-context-profile-detail';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const PlayerGameMapDetailSidePeek = ({
  game_map_id: gameMapId,
}: PlayerGameMapDetailSidePeekProps): ReactNode => {
  const {
    game_map: gameMap,
    loading,
    error,
  } = usePlayerGameMapDetail(gameMapId);

  const [activeGemDetail, setActiveGemDetail] =
    useState<PlayerGameMapGemDetail | null>(null);

  const handleCloseGemDetail = () => {
    setActiveGemDetail(null);
  };

  if (loading) {
    return (
      <div className="px-4">
        <InfiniteLoader />
      </div>
    );
  }

  if (error || !gameMap) {
    return (
      <div className="px-4">
        <ApiErrorAlert
          apiError={error?.message ?? 'Unable to load this Game Map.'}
        />
      </div>
    );
  }

  const gemContext = gameMap.gem_context;

  const renderGemSection = (): ReactNode => {
    if (!gemContext) {
      return null;
    }

    return (
      <PlayerGameMapGemSection
        gem_context={gemContext}
        on_view_gem_profile={() =>
          setActiveGemDetail(PlayerGameMapGemDetail.PROFILE)
        }
        on_view_gem_effects={() =>
          setActiveGemDetail(PlayerGameMapGemDetail.EFFECTS)
        }
      />
    );
  };

  const renderGemProfileDetail = (): ReactNode => {
    if (!gemContext || activeGemDetail !== PlayerGameMapGemDetail.PROFILE) {
      return null;
    }

    return (
      <StackedCard
        on_close={handleCloseGemDetail}
        content_mode={StackedCardContentMode.FULL_BLEED}
        aria_label="Gem Profile"
      >
        <GemContextProfileDetail
          context={gemContext}
          description="These rolled Gems define the base modifiers and rules applied on this Game Map."
        />
      </StackedCard>
    );
  };

  const renderGemEffectsDetail = (): ReactNode => {
    if (!gemContext || activeGemDetail !== PlayerGameMapGemDetail.EFFECTS) {
      return null;
    }

    return (
      <StackedCard
        on_close={handleCloseGemDetail}
        content_mode={StackedCardContentMode.FULL_BLEED}
        aria_label="Gem Effects"
      >
        <GemContextEffectsDetail context={gemContext} />
      </StackedCard>
    );
  };

  return (
    <div className="flex flex-col gap-4 px-4">
      <h1 className="text-glacier-900 dark:text-glacier-100 text-xl font-semibold">
        {gameMap.name}
      </h1>
      <GameMapDetail
        game_map={gameMap}
        single_column
        gem_section={renderGemSection()}
      />
      {renderGemProfileDetail()}
      {renderGemEffectsDetail()}
    </div>
  );
};

export default PlayerGameMapDetailSidePeek;
