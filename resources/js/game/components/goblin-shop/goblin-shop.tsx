import React, { useState } from 'react';

import GoblinShopCard from './components/goblin-shop-card';
import GoblinShopItemView from './components/goblin-shop-item-view';
import { GoblinShopContext } from './context/goblin-shop-context';
import GoblinShopProps from './types/goblin-shop-props';
import { useCustomContext } from '../../../utils/hooks/use-custom-context';
import BaseUsableItemDefinition from '../../api-definitions/items/usable-item-definitions/base-usable-item-definition';
import { formatNumberWithCommas } from '../../util/format-number';
import { isNilOrZeroValue } from '../../util/general-util';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';
import InfiniteRow from 'ui/infinite-scroll/components/infitnite-row';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';

const GoblinShop = ({ on_close }: GoblinShopProps) => {
  const [itemToView, setItemToView] = useState<BaseUsableItemDefinition | null>(
    null
  );

  const { data, loading, error, handleScroll, gold_bars, inventoryIsFull } =
    useCustomContext(GoblinShopContext, 'GoblinShop');

  const handleViewItem = (item_id: number) => {
    const foundItem = data.find((item) => item.item_id === item_id);

    if (!foundItem) {
      return;
    }

    setItemToView(foundItem);
  };

  const handleCloseItemView = () => {
    setItemToView(null);
  };

  const renderContent = () => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error) {
      return <Alert variant={AlertVariant.DANGER}>{error.message}</Alert>;
    }

    return (
      <InfiniteRow handle_scroll={handleScroll} additional_css="max-h-[500px]">
        {data.map((item) => (
          <GoblinShopCard
            key={item.item_id}
            item={item}
            view_item={handleViewItem}
            action_disabled={inventoryIsFull || gold_bars <= 0}
          />
        ))}
      </InfiniteRow>
    );
  };

  const renderCharacterGoldBars = () => {
    if (isNilOrZeroValue(gold_bars)) {
      return null;
    }

    return (
      <p className="mb-4 text-gray-800 dark:text-gray-300">
        <strong>
          <span className="text-marigold-600 dark:text-mango-tango-400">
            Your Gold Bars:
          </span>
        </strong>{' '}
        {formatNumberWithCommas(gold_bars)}
      </p>
    );
  };

  const renderInventoryIsFullNotice = () => {
    if (!inventoryIsFull) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.WARNING}>
        Your inventory is currently full. You cannot purchase any items, the
        shop keeper is sad.
      </Alert>
    );
  };

  const renderNoGoldBarsNotice = () => {
    if (gold_bars > 0) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.WARNING}>
        You have no gold bars. The goblin shop owner is displeased. "Don't you
        convert your treasure child?" he asks with a air aof disgust.
      </Alert>
    );
  };

  if (itemToView) {
    return (
      <GoblinShopItemView item={itemToView} on_close={handleCloseItemView} />
    );
  }

  return (
    <ContainerWithTitle manageSectionVisibility={on_close} title="Goblin Shop">
      <Card>
        <p className="my-4 text-gray-800 italic dark:text-gray-300">
          Hello my child. Do you have any gold bars? I love me some shiny gold
          bars. I have things for you in exchange for those gold bars! Take a
          look see.
        </p>
        <Separator />
        {renderCharacterGoldBars()}
        {renderNoGoldBarsNotice()}
        {renderInventoryIsFullNotice()}
        <Separator />
        {renderContent()}
      </Card>
    </ContainerWithTitle>
  );
};

export default GoblinShop;
