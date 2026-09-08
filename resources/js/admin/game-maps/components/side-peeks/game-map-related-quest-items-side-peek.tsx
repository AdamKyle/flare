import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import GameMapRelatedQuestItemsSidePeekProps from './types/game-map-related-quest-items-side-peek-props';
import { resolveSidePeekComponent } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-mapper';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import ReadOnlyItemCardDensity from '../../../../game/components/side-peeks/components/items/enums/read-only-item-card-density';
import ReadOnlyItemCard from '../../../../game/components/side-peeks/components/items/read-only-item-card';
import { useGameMapRelatedQuestItems } from '../../api/hooks/use-game-map-related-quest-items';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const GameMapRelatedQuestItemsSidePeek = ({
  game_map_id: gameMapId,
}: GameMapRelatedQuestItemsSidePeekProps): ReactNode => {
  const questItems = useGameMapRelatedQuestItems(gameMapId);
  const [selectedItemId, setSelectedItemId] = useState<number | null>(null);

  const handleOpenItem = (id: number): void => {
    setSelectedItemId(id);
  };

  const handleCloseItem = (): void => {
    setSelectedItemId(null);
  };

  const handleScroll = (event: React.UIEvent<HTMLDivElement>): void => {
    const target = event.currentTarget;
    const nearBottom =
      target.scrollHeight - target.scrollTop - target.clientHeight < 100;

    if (nearBottom) {
      questItems.on_end_reached();
    }
  };

  const renderSelectedItem = (): ReactNode => {
    if (selectedItemId === null) {
      return null;
    }

    const AdminItemDetail = resolveSidePeekComponent(
      SidePeekComponentRegistrationEnum.ADMIN_ITEM_DETAIL
    );

    return (
      <StackedCard
        on_close={handleCloseItem}
        aria_label="Item Details"
        content_mode={StackedCardContentMode.FULL_BLEED}
      >
        <AdminItemDetail
          is_open
          title="Item Details"
          item_id={selectedItemId}
        />
      </StackedCard>
    );
  };

  const renderContent = (): ReactNode => {
    if (questItems.loading) {
      return <InfiniteLoader />;
    }

    if (questItems.error) {
      return (
        <div className="px-4 py-3">
          <ApiErrorAlert
            apiError={questItems.error.message ?? 'Unable to load Quest Items.'}
          />
        </div>
      );
    }

    if (questItems.data.length === 0) {
      return (
        <p className="text-glacier-700 dark:text-glacier-300 px-4 py-3 text-sm">
          No quest Items are connected to this Game Map.
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
            {questItems.data.map((item) => (
              <ReadOnlyItemCard
                key={item.item_id}
                item_id={item.item_id}
                name={item.name}
                description={item.description}
                effect={item.effect}
                usable={item.usable}
                density={ReadOnlyItemCardDensity.COMPACT}
                on_click={handleOpenItem}
              />
            ))}
            {questItems.is_loading_more && <InfiniteLoader />}
          </div>
        </InfiniteScroll>
      </div>
    );
  };

  return (
    <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
      {renderContent()}
      {renderSelectedItem()}
    </div>
  );
};

export default GameMapRelatedQuestItemsSidePeek;
