import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import GameMapRelatedQuestsSidePeekProps from './types/game-map-related-quests-side-peek-props';
import { resolveSidePeekComponent } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-mapper';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { QUEST_KIND_LABELS } from '../../../../game/reusable-components/quest/enums/quest-kind';
import GameMapRelatedQuestDefinition from '../../api/definitions/game-map-related-quest-definition';
import { useGameMapRelatedQuests } from '../../api/hooks/use-game-map-related-quests';

import StackedCard from 'ui/cards/stacked-card';
import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

/**
 * Bounded, append-paginated browser for the Quests whose Quest-giver NPC
 * belongs to a Game Map, opened from the Game Map's "Related Game Data"
 * hub. Each result opens the canonical Quest detail inside a `StackedCard`
 * while preserving this relationship browser underneath (mounted, with its
 * scroll position intact) rather than replacing it through the global
 * SidePeek emitter.
 */
const GameMapRelatedQuestsSidePeek = ({
  game_map_id: gameMapId,
}: GameMapRelatedQuestsSidePeekProps): ReactNode => {
  const quests = useGameMapRelatedQuests(gameMapId);
  const [selectedQuestId, setSelectedQuestId] = useState<number | null>(null);

  const handleOpenQuest = (id: number): void => {
    setSelectedQuestId(id);
  };

  const handleCloseQuest = (): void => {
    setSelectedQuestId(null);
  };

  const handleScroll = (event: React.UIEvent<HTMLDivElement>): void => {
    const target = event.currentTarget;
    const nearBottom =
      target.scrollHeight - target.scrollTop - target.clientHeight < 100;

    if (nearBottom) {
      quests.on_end_reached();
    }
  };

  const renderRow = (quest: GameMapRelatedQuestDefinition): ReactNode => (
    <button
      key={quest.id}
      type="button"
      onClick={() => handleOpenQuest(quest.id)}
      className="border-glacier-200 dark:border-glacier-800 bg-glacier-50 dark:bg-glacier-900/40 hover:bg-glacier-100 dark:hover:bg-glacier-900 focus-visible:ring-danube-400 w-full rounded-md border px-3 py-2 text-left focus:outline-none focus-visible:ring-2"
    >
      <p className="text-glacier-900 dark:text-glacier-100 font-medium">
        {quest.name}
      </p>
      <p className="text-glacier-600 dark:text-glacier-400 text-xs">
        {QUEST_KIND_LABELS[quest.kind]}
        {quest.npc && ` · ${quest.npc.name}`}
      </p>
    </button>
  );

  const renderSelectedQuest = (): ReactNode => {
    if (selectedQuestId === null) {
      return null;
    }

    const AdminQuestDetail = resolveSidePeekComponent(
      SidePeekComponentRegistrationEnum.ADMIN_QUEST_DETAIL
    );

    return (
      <StackedCard on_close={handleCloseQuest} aria_label="Quest Details">
        <AdminQuestDetail
          is_open
          title="Quest Details"
          quest_id={selectedQuestId}
        />
      </StackedCard>
    );
  };

  const renderContent = (): ReactNode => {
    if (quests.loading) {
      return <InfiniteLoader />;
    }

    if (quests.error) {
      return (
        <div className="px-4">
          <ApiErrorAlert
            apiError={quests.error.message ?? 'Unable to load Quests.'}
          />
        </div>
      );
    }

    if (quests.data.length === 0) {
      return (
        <p className="text-glacier-700 dark:text-glacier-300 px-4 text-sm">
          No Quest Giver on this Game Map offers any Quests.
        </p>
      );
    }

    return (
      <div className="h-[500px] max-h-[500px] px-4">
        <InfiniteScroll handle_scroll={handleScroll}>
          <div className="flex flex-col gap-2">
            {quests.data.map(renderRow)}
            {quests.is_loading_more && <InfiniteLoader />}
          </div>
        </InfiniteScroll>
      </div>
    );
  };

  return (
    <div className="space-y-4">
      {renderContent()}
      {renderSelectedQuest()}
    </div>
  );
};

export default GameMapRelatedQuestsSidePeek;
