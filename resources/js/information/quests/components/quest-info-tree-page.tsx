import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useMemo, useState } from 'react';

import QuestTreeNodeDefinition from '../../../game/reusable-components/quest/api/definitions/quest-tree-node-definition';
import QuestTree from '../../../game/reusable-components/quest/components/quest-tree';
import {
  isQuestKind,
  QUEST_KIND_LABELS,
  QuestKind,
} from '../../../game/reusable-components/quest/enums/quest-kind';
import { usePublicQuestTree } from '../api/hooks/use-public-quest-tree';
import { parseNumberOption } from '../utils/parse-quest-info-dropdown-value';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const KIND_ITEMS: DropdownItem[] = Object.values(QuestKind).map((kind) => ({
  label: QUEST_KIND_LABELS[kind],
  value: kind,
}));

const collectMapItems = (
  quests: QuestTreeNodeDefinition[],
  seen: Map<number, DropdownItem>
): void => {
  quests.forEach((quest) => {
    if (quest.game_map && !seen.has(quest.game_map.id)) {
      seen.set(quest.game_map.id, {
        label: quest.game_map.name,
        value: quest.game_map.id,
      });
    }

    collectMapItems(quest.children, seen);
  });
};

const navigateToQuest = (id: number): void => {
  window.location.href = `/information/quests/${id}`;
};

/**
 * Public, read-only Quest tree page. Reuses the same factual Quest tree
 * presentation and Map/Kind filters as Admin, without any mutation controls.
 */
const QuestInfoTreePage = (): ReactNode => {
  const [mapId, setMapId] = useState<number | null>(null);
  const [kind, setKind] = useState<QuestKind | null>(null);

  const { quests: allQuests } = usePublicQuestTree(null, null);
  const { quests, loading, error } = usePublicQuestTree(mapId, kind);

  const mapItems = useMemo(() => {
    const seen = new Map<number, DropdownItem>();
    collectMapItems(allQuests, seen);

    return Array.from(seen.values()).sort((a, b) =>
      a.label.localeCompare(b.label)
    );
  }, [allQuests]);

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error) {
      return <ApiErrorAlert apiError={error.message} />;
    }

    if (quests.length === 0) {
      return (
        <p className="text-glacier-600 dark:text-glacier-400 text-sm">
          No Quests match the current filters.
        </p>
      );
    }

    return (
      <QuestTree
        quests={quests}
        completed_quest_ids={[]}
        navigation={{ on_open_quest: navigateToQuest }}
      />
    );
  };

  return (
    <div>
      <div className="mb-4 flex flex-wrap items-center gap-3">
        <div className="w-full max-w-xs">
          <Dropdown
            id="quest-info-map-filter"
            aria_label="Filter by Game Map"
            searchable
            items={mapItems}
            pre_selected_item={mapItems.find((item) => item.value === mapId)}
            on_select={(item) => setMapId(parseNumberOption(item.value))}
            on_clear={() => setMapId(null)}
            selection_placeholder="All Maps"
          />
        </div>

        <div className="w-full max-w-xs">
          <Dropdown
            id="quest-info-kind-filter"
            aria_label="Filter by Quest kind"
            items={KIND_ITEMS}
            pre_selected_item={KIND_ITEMS.find((item) => item.value === kind)}
            on_select={(item) => {
              if (!isQuestKind(item.value)) {
                return;
              }

              setKind(item.value);
            }}
            on_clear={() => setKind(null)}
            selection_placeholder="All Kinds"
          />
        </div>
      </div>

      {renderContent()}
    </div>
  );
};

export default QuestInfoTreePage;
