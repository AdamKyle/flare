import { isEmpty } from 'lodash';
import React, { ReactNode } from 'react';

import BaseInventoryItemDefinition from './api-definitions/base-inventory-item-definition';
import { useInfiniteScroll } from './hooks/use-infinite-scroll';
import UsableInventoryProps from './types/usable-inventory-props';
import UsableItem from './usable-item';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import BackButton from 'ui/buttons/back-button';
import { InfiniteScroll } from 'ui/infinite-scroll/infinite-scroll';
import Separator from 'ui/seperatror/separator';

const Backpack = ({
  close_usable_Section,
  usable_items,
}: UsableInventoryProps): ReactNode => {
  const usableItems = usable_items.filter((item) => item.usable);
  const damagesKingdomsItems = usable_items.filter(
    (item) => item.damages_kingdoms
  );

  const {
    visibleItems: visibleUsableItems,
    handleScroll: handleInventoryScroll,
  } = useInfiniteScroll({ items: usableItems, chunkSize: 5 });
  const {
    visibleItems: visibleDamagesKingdomItems,
    handleScroll: handleQuestScroll,
  } = useInfiniteScroll({ items: damagesKingdomsItems, chunkSize: 5 });

  return (
    <>
      <BackButton
        title={'Back to Inventory'}
        handle_back={close_usable_Section}
      />
      <Separator />
      <div className="grid lg:grid-cols-2 gap-4">
        <div>
          <h4 className="text-primary-500 dark:text-primary-200">
            Usable Items
          </h4>
          <Alert variant={AlertVariant.INFO}>
            <p>
              These items can be used on your character to increase skills,
              stats, damage and other aspects of your character.
            </p>
          </Alert>
          <InfiniteScroll
            handle_scroll={handleInventoryScroll}
            additional_css={'my-4'}
          >
            {isEmpty(visibleUsableItems) && (
              <div className="text-center py-4">
                You have no usable items that you can use to make your self more
                powerful! Try using Alchemy, which you can unlock through a
                quest, to craft some items that can empower you to take on
                stronger critters!
              </div>
            )}
            {visibleUsableItems.map((item) => (
              <UsableItem
                key={item.slot_id}
                item={item as BaseInventoryItemDefinition}
              />
            ))}
          </InfiniteScroll>
        </div>
        <div>
          <h4 className="text-primary-500 dark:text-primary-200">
            Damages Kingdoms Items
          </h4>
          <Alert variant={AlertVariant.INFO}>
            <p>
              These items can only be used when you go to attack other kingdoms,
              you can "drop" them on kingdoms to do massive amounts of damage.
            </p>
          </Alert>
          <InfiniteScroll
            handle_scroll={handleQuestScroll}
            additional_css={'my-4'}
          >
            {isEmpty(visibleDamagesKingdomItems) && (
              <div className="text-center py-4">
                You have no items that damage kingdoms. Try using Alchemy, which
                you can unlock through a quest, to craft some and then take over
                the plane by dropping them on other peoples kingdoms!
              </div>
            )}
            {visibleDamagesKingdomItems.map((item) => (
              <UsableItem
                key={item.slot_id}
                item={item as BaseInventoryItemDefinition}
              />
            ))}
          </InfiniteScroll>
        </div>
      </div>
    </>
  );
};

export default Backpack;
