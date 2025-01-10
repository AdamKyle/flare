import React, { ReactNode } from 'react';

import BackpackItem from './backpack-item';
import { useInfiniteScroll } from './hooks/use-infinite-scroll';
import BackpackState from './types/backpack-state';

import BackButton from 'ui/buttons/back-button';
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
          <div
            className="my-4 h-96 overflow-y-auto scrollbar-thin scrollbar-thumb-primary-300 scrollbar-track-primary-100 dark:scrollbar-thumb-primary-400 dark:scrollbar-track-primary-200 scrollbar-thumb-rounded-md px-2"
            onScroll={handleInventoryScroll}
          >
            {visibleInventoryItems.map((item) => (
              <BackpackItem key={item.slot_id} item={item} />
            ))}
          </div>
        </div>
        <div>
          <h4 className="text-primary-500 dark:text-primary-200">
            Quest Items
          </h4>
          <div
            className="my-4 h-96 overflow-y-auto scrollbar-thin scrollbar-thumb-primary-300 scrollbar-track-primary-100 dark:scrollbar-thumb-primary-400 dark:scrollbar-track-primary-200 scrollbar-thumb-rounded-md px-2"
            onScroll={handleQuestScroll}
          >
            {visibleQuestItems.map((item) => (
              <BackpackItem key={item.slot_id} item={item} />
            ))}
          </div>
        </div>
      </div>
    </>
  );
};

export default Backpack;
