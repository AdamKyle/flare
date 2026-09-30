import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useEffect, useState } from 'react';

import PlayerGameMapDetailStack from './player-game-map-detail-stack';
import PlayerLocationDetailStack from './player-location-detail-stack';
import PlayerMonsterDetailStackProps from './types/player-monster-detail-stack-props';
import { MonsterGemEffectContextDefinition } from '../../../../reusable-components/monster/api/definitions/monster-detail-definition';
import { usePlayerMonsterGemEffectContexts } from '../../../../reusable-components/monster/api/hooks/use-player-monster-gem-effect-contexts';
import MonsterDetail from '../../../../reusable-components/monster/components/monster-detail';
import MonsterGemEffectContextCard from '../../../../reusable-components/monster/components/monster-gem-effect-context-card';
import { useFetchMonsterStatsApi } from '../../../actions/partials/monster-stat-section/api/hooks/use-fetch-monster-stats-api';
import QuestItemDetailStack from '../../character-inventory/inventory-item/quest-item-detail-stack';

import { useGameData } from 'game-data/hooks/use-game-data';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const PlayerMonsterDetailStack = ({
  monster_id: monsterId,
  on_close: onClose,
}: PlayerMonsterDetailStackProps): ReactNode => {
  const { gameData } = useGameData();
  const { loading, data, error, setRequestParams } = useFetchMonsterStatsApi();
  const [nestedGameMapId, setNestedGameMapId] = useState<number | null>(null);
  const [nestedLocationId, setNestedLocationId] = useState<number | null>(null);
  const [nestedQuestItemOpen, setNestedQuestItemOpen] = useState(false);
  const [selectedContext, setSelectedContext] =
    useState<MonsterGemEffectContextDefinition | null>(null);

  const gemEffectContexts = usePlayerMonsterGemEffectContexts({
    monster_id: monsterId,
    enabled: (data?.gem_effect_context_count ?? 0) > 1,
  });

  useEffect(() => {
    if (!gameData?.character?.id) {
      return;
    }

    setRequestParams({
      character_id: gameData.character.id,
      monster_id: monsterId,
    });
  }, [gameData?.character?.id, monsterId, setRequestParams]);

  const navigation = {
    on_open_map: setNestedGameMapId,
    on_open_location: setNestedLocationId,
    on_open_item: (itemId: number): void => {
      if (data?.quest_and_celestial.quest_item?.item_id === itemId) {
        setNestedQuestItemOpen(true);
      }
    },
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !data) {
      return (
        <ApiErrorAlert
          apiError={error?.message ?? 'Unable to load this Monster.'}
        />
      );
    }

    return (
      <MonsterDetail
        monster={data}
        navigation={navigation}
        initial_context_tab={data.gem_effect_context_count > 0}
        gem_effect_context_browser={{
          context_rows: gemEffectContexts.context_rows,
          loading: gemEffectContexts.loading,
          loading_more: gemEffectContexts.loading_more,
          error: gemEffectContexts.error,
          has_more: gemEffectContexts.has_more,
          on_load_next: gemEffectContexts.load_next,
          on_open_context: setSelectedContext,
        }}
      />
    );
  };

  return (
    <StackedCard
      on_close={onClose}
      aria_label="Monster Details"
      content_mode={StackedCardContentMode.FULL_BLEED}
    >
      <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
        <div className="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5">
          {renderContent()}
        </div>

        {selectedContext ? (
          <StackedCard
            on_close={() => setSelectedContext(null)}
            aria_label={selectedContext.label}
            content_mode={StackedCardContentMode.FULL_BLEED}
          >
            <div className="h-full min-h-0 overflow-y-auto p-4">
              <MonsterGemEffectContextCard
                context={selectedContext}
                navigation={navigation}
                single_column
              />
            </div>
          </StackedCard>
        ) : null}

        {nestedGameMapId !== null ? (
          <PlayerGameMapDetailStack
            game_map_id={nestedGameMapId}
            on_close={() => setNestedGameMapId(null)}
          />
        ) : null}

        {nestedLocationId !== null ? (
          <PlayerLocationDetailStack
            location_id={nestedLocationId}
            on_close={() => setNestedLocationId(null)}
          />
        ) : null}

        {nestedQuestItemOpen && data?.quest_and_celestial.quest_item ? (
          <QuestItemDetailStack
            quest_item={data.quest_and_celestial.quest_item}
            on_close={() => setNestedQuestItemOpen(false)}
          />
        ) : null}
      </div>
    </StackedCard>
  );
};

export default PlayerMonsterDetailStack;
