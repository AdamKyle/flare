import React, { ReactNode } from 'react';

import NpcDetailBodyProps from './types/npc-detail-body-props';
import { NpcApiMessages } from '../api/enums/npc-api-messages';
import { NpcQuestRelatedItemDefinition } from '../api/definitions/npc-quest-definition';
import { NPC_TYPE_LABELS } from '../enums/npc-type';

import AdminQuestItemPresentationDefinition from '../../items/api/definitions/admin-quest-item-presentation-definition';

import ApiErrorAlert from 'api-handler/components/api-error-alert';
import DataTablePagination from 'ui/data-table/data-table-pagination';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
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
}: NpcDetailBodyProps): ReactNode => {
  const renderItemLink = (
    item: NpcQuestRelatedItemDefinition | null
  ): ReactNode => {
    if (!item) {
      return (
        <span className="text-glacier-600 dark:text-glacier-400">None</span>
      );
    }

    return (
      <button
        type="button"
        onClick={() => onOpenItem(item.id, item.name)}
        className="text-danube-600 dark:text-danube-300 focus-visible:ring-danube-400 rounded-sm font-medium hover:underline focus:outline-none focus-visible:ring-2"
      >
        {item.name}
      </button>
    );
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
      <>
        <ul className="divide-glacier-200 dark:divide-glacier-800 divide-y">
          {quests.data.map((quest) => (
            <li key={quest.id} className="space-y-1 px-2 py-3">
              <p className="text-glacier-900 dark:text-glacier-100 font-medium">
                {quest.name}
              </p>
              <div className="text-glacier-700 dark:text-glacier-300 flex flex-wrap gap-x-6 gap-y-1 text-sm">
                <span>Required: {renderItemLink(quest.required_item)}</span>
                <span>
                  Secondary: {renderItemLink(quest.secondary_required_item)}
                </span>
                <span>Reward: {renderItemLink(quest.reward_item)}</span>
              </div>
            </li>
          ))}
        </ul>
        <DataTablePagination
          current_page={quests.page}
          total_pages={quests.total_pages}
          total_records={quests.total_records}
          on_page_change={quests.set_page}
        />
      </>
    );
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
      <>
        <ul className="divide-glacier-200 dark:divide-glacier-800 divide-y">
          {rewardItems.data.map(
            (item: AdminQuestItemPresentationDefinition) => (
              <li key={item.item_id}>
                <button
                  type="button"
                  onClick={() => onOpenItem(item.item_id, item.name)}
                  className="hover:bg-glacier-50 dark:hover:bg-glacier-900 focus-visible:ring-glacier-400 flex w-full items-center justify-between gap-3 px-2 py-3 text-left focus:outline-none focus-visible:ring-2"
                >
                  <span className="text-glacier-900 dark:text-glacier-100 font-medium">
                    {item.name}
                  </span>
                </button>
              </li>
            )
          )}
        </ul>
        <DataTablePagination
          current_page={rewardItems.page}
          total_pages={rewardItems.total_pages}
          total_records={rewardItems.total_records}
          on_page_change={rewardItems.set_page}
        />
      </>
    );
  };

  return (
    <div className="flex flex-col gap-6">
      <section>
        <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
          Identity
        </h2>
        <Dl>
          <Dt>Map</Dt>
          <Dd>{npc.game_map.name}</Dd>
          <Dt>Type</Dt>
          <Dd>{NPC_TYPE_LABELS[npc.type]}</Dd>
          <Dt>Coordinates</Dt>
          <Dd>
            X {npc.x_position}, Y {npc.y_position}
          </Dd>
        </Dl>
      </section>

      <section>
        <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
          Quests ({npc.quest_count})
        </h2>
        {renderQuests()}
      </section>

      <section>
        <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
          Quest Items Given by This NPC ({npc.reward_item_count})
        </h2>
        {renderRewardItems()}
      </section>
    </div>
  );
};

export default NpcDetailBody;
