import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import NpcQuestRelationshipCard from './npc-quest-relationship-card';
import NpcDetailBodyProps from './types/npc-detail-body-props';
import ReadOnlyItemCard from '../../../game/components/side-peeks/components/items/read-only-item-card';
import AdminQuestItemPresentationDefinition from '../../items/api/definitions/admin-quest-item-presentation-definition';
import { NpcApiMessages } from '../api/enums/npc-api-messages';
import { NPC_TYPE_LABELS } from '../enums/npc-type';

import Card from 'ui/cards/card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

/**
 * Render the canonical read-only NPC detail body: Identity, paginated
 * Quests with clickable required/secondary/reward Item identities, and a
 * paginated, deduplicated list of Quest Items given by this NPC. Reused by
 * both the standalone NPC show screen and the Game Map NPC side-peek so
 * every entry point renders the same factual content.
 */
const NpcDetailBody = ({
  npc,
  quests,
  reward_items: rewardItems,
  on_open_item: onOpenItem,
  on_open_quest: onOpenQuest,
  on_open_map: onOpenMap,
}: NpcDetailBodyProps): ReactNode => {
  const renderMap = (): ReactNode => {
    if (!onOpenMap) {
      return npc.game_map.name;
    }

    return (
      <button
        type="button"
        onClick={() => onOpenMap(npc.game_map.id)}
        className="text-danube-700 hover:text-danube-600 dark:text-danube-200 dark:hover:text-danube-100 decoration-danube-400 dark:decoration-danube-500 focus-visible:ring-danube-400 rounded-sm font-medium underline underline-offset-2 focus:outline-none focus-visible:ring-2"
      >
        {npc.game_map.name}
      </button>
    );
  };

  const handleQuestsScroll = (event: React.UIEvent<HTMLDivElement>) => {
    const target = event.currentTarget;
    const nearBottom =
      target.scrollHeight - target.scrollTop - target.clientHeight < 100;

    if (nearBottom) {
      quests.on_end_reached();
    }
  };

  const renderQuests = (): ReactNode => {
    if (quests.loading) {
      return <InfiniteLoader />;
    }

    if (quests.error) {
      return (
        <ApiErrorAlert
          apiError={quests.error.message ?? NpcApiMessages.LoadQuests}
        />
      );
    }

    if (quests.data.length === 0) {
      return (
        <p className="text-glacier-700 dark:text-glacier-300 text-sm">
          This NPC does not give any Quests.
        </p>
      );
    }

    return (
      <InfiniteScroll
        handle_scroll={handleQuestsScroll}
        height_class="h-auto max-h-[500px]"
      >
        <div className="flex flex-col gap-2">
          {quests.data.map((quest) => (
            <NpcQuestRelationshipCard
              key={quest.id}
              quest={quest}
              on_open_quest={onOpenQuest}
              on_open_item={onOpenItem}
            />
          ))}
          {quests.is_loading_more && <InfiniteLoader />}
        </div>
      </InfiniteScroll>
    );
  };

  const handleRewardItemsScroll = (event: React.UIEvent<HTMLDivElement>) => {
    const target = event.currentTarget;
    const nearBottom =
      target.scrollHeight - target.scrollTop - target.clientHeight < 100;

    if (nearBottom) {
      rewardItems.on_end_reached();
    }
  };

  const renderRewardItems = (): ReactNode => {
    if (rewardItems.loading) {
      return <InfiniteLoader />;
    }

    if (rewardItems.error) {
      return (
        <ApiErrorAlert
          apiError={rewardItems.error.message ?? NpcApiMessages.LoadRewardItems}
        />
      );
    }

    if (rewardItems.data.length === 0) {
      return (
        <p className="text-glacier-700 dark:text-glacier-300 text-sm">
          This NPC&apos;s Quests do not award any Items.
        </p>
      );
    }

    return (
      <InfiniteScroll
        handle_scroll={handleRewardItemsScroll}
        height_class="h-auto max-h-[500px]"
      >
        <div className="flex flex-col gap-2">
          {rewardItems.data.map(
            (item: AdminQuestItemPresentationDefinition) => (
              <ReadOnlyItemCard
                key={item.item_id}
                item_id={item.item_id}
                name={item.name}
                description={item.description}
                effect={item.effect}
                usable={item.usable}
                on_click={() => onOpenItem(item.item_id, item.name)}
              />
            )
          )}
          {rewardItems.is_loading_more && <InfiniteLoader />}
        </div>
      </InfiniteScroll>
    );
  };

  return (
    <div className="flex flex-col gap-6">
      <h1 className="text-glacier-900 dark:text-glacier-100 text-xl font-semibold">
        {npc.real_name}
      </h1>

      <Card>
        <section>
          <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
            NPC Details
          </h2>
          <Dl>
            <Dt>Map</Dt>
            <Dd>{renderMap()}</Dd>
            <Dt>Type</Dt>
            <Dd>{NPC_TYPE_LABELS[npc.type]}</Dd>
            <Dt>Coordinates</Dt>
            <Dd>
              X {npc.x_position}, Y {npc.y_position}
            </Dd>
          </Dl>
        </section>
      </Card>

      <Card>
        <section>
          <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
            Quests ({npc.quest_count})
          </h2>
          {renderQuests()}
        </section>
      </Card>

      <Card>
        <section>
          <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
            Quest Items Given by This NPC ({npc.reward_item_count})
          </h2>
          {renderRewardItems()}
        </section>
      </Card>
    </div>
  );
};

export default NpcDetailBody;
