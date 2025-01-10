import { isEmpty } from 'lodash';
import React, { ReactNode } from 'react';

import BackpackItem from './backpack-item';
import { useInfiniteScroll } from './hooks/use-infinite-scroll';
import BackpackState from './types/backpack-state';

import BackButton from 'ui/buttons/back-button';
import { InfiniteScroll } from 'ui/infinite-scroll/infinite-scroll';
import Separator from 'ui/seperatror/separator';

const Backpack = ({
  close_backpack,
  inventory_items,
  quest_items,
}: BackpackState): ReactNode => {
  const {
    visibleItems: visibleInventoryItems,
    handleScroll: handleInventoryScroll,
  } = useInfiniteScroll({ items: inventory_items, chunkSize: 5 });
  const { visibleItems: visibleQuestItems, handleScroll: handleQuestScroll } =
    useInfiniteScroll({ items: quest_items, chunkSize: 5 });

  return (
    <>
      <BackButton title={'Back to Inventory'} handle_back={close_backpack} />
      <Separator />
      <div className="grid lg:grid-cols-2 gap-4">
        <div>
          <h4 className="text-primary-500 dark:text-primary-200">Backpack</h4>
          <InfiniteScroll
            handle_scroll={handleInventoryScroll}
            additional_css={'my-4'}
          >
            {isEmpty(visibleInventoryItems) && (
              <div className="text-center py-4">
                You have no weapons or armour or other types of items you can
                equip. Try crafting some, fighting monsters or buying some from
                the shop.
              </div>
            )}
            {visibleInventoryItems.map((item) => (
              <BackpackItem key={item.slot_id} item={item} />
            ))}
          </InfiniteScroll>
        </div>
        <div>
          <h4 className="text-primary-500 dark:text-primary-200">
            Quest Items
          </h4>
          <InfiniteScroll
            handle_scroll={handleQuestScroll}
            additional_css={'my-4'}
          >
            {isEmpty(visibleQuestItems) && (
              <div className="text-center py-4">
                You have no quest items. Quest items are obtained by visiting
                locations and completing quests. Some items are used in
                subsequent quests while others have special effects that can
                unlock additional game content.
              </div>
            )}
            {visibleQuestItems.map((item) => (
              <BackpackItem key={item.slot_id} item={item} />
            ))}
          </InfiniteScroll>
        </div>
      </div>
    </>
  );
};

export default Backpack;
