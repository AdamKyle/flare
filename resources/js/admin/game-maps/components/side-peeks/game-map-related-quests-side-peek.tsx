import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import GameMapRelatedQuestsSidePeekProps from './types/game-map-related-quests-side-peek-props';
import { resolveSidePeekComponent } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-mapper';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import QuestCard from '../../../../game/reusable-components/quest/components/quest-card';
import { QUEST_KIND_LABELS } from '../../../../game/reusable-components/quest/enums/quest-kind';
import GameMapRelatedQuestDefinition from '../../api/definitions/game-map-related-quest-definition';
import { useGameMapRelatedQuests } from '../../api/hooks/use-game-map-related-quests';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

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
    <QuestCard
      key={quest.id}
      quest_id={quest.id}
      name={quest.name}
      kind_label={QUEST_KIND_LABELS[quest.kind]}
      npc_name={quest.npc?.name ?? null}
      on_open_quest={handleOpenQuest}
    />
  );

  const renderSelectedQuest = (): ReactNode => {
    if (selectedQuestId === null) {
      return null;
    }

    const AdminQuestDetail = resolveSidePeekComponent(
      SidePeekComponentRegistrationEnum.ADMIN_QUEST_DETAIL
    );

    return (
      <StackedCard
        on_close={handleCloseQuest}
        aria_label="Quest Details"
        content_mode={StackedCardContentMode.FULL_BLEED}
      >
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
        <div className="px-4 py-3">
          <ApiErrorAlert
            apiError={quests.error.message ?? 'Unable to load Quests.'}
          />
        </div>
      );
    }

    if (quests.data.length === 0) {
      return (
        <p className="text-glacier-700 dark:text-glacier-300 px-4 py-3 text-sm">
          No Quest Giver on this Game Map offers any Quests.
        </p>
      );
    }

    return (
      <div className="min-h-0 flex-1 px-2 py-2">
        <InfiniteScroll
          handle_scroll={handleScroll}
          height_class="h-full min-h-0"
        >
          <div className="flex flex-col gap-2">
            {quests.data.map(renderRow)}
            {quests.is_loading_more && <InfiniteLoader />}
          </div>
        </InfiniteScroll>
      </div>
    );
  };

  return (
    <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
      {renderContent()}
      {renderSelectedQuest()}
    </div>
  );
};

export default GameMapRelatedQuestsSidePeek;
