import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { isNil } from 'lodash';
import React from 'react';

import ItemComparison from '../../../reusable-components/item/item-comparison';
import { ShopApiUrls } from '../api/enums/shop-api-urls';
import { useCompareItemApi } from '../api/hooks/use-compare-item-api';
import ComparisonProps from '../types/comparison-props';

import { GameDataError } from 'game-data/components/game-data-error';
import { useGameData } from 'game-data/hooks/use-game-data';

import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const ShopComparison = ({
  item_name,
  item_type,
  close_comparison,
}: ComparisonProps) => {
  const { gameData } = useGameData();

  const { loading, error, data } = useCompareItemApi({
    characterData: gameData?.character,
    item_name,
    item_type,
    url: ShopApiUrls.COMPARE_ITEMS,
  });

  const renderContent = () => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (isNil(data)) {
      return <GameDataError />;
    }

    if (!isNil(error)) {
      return <ApiErrorAlert apiError={error.message} />;
    }

    return <ItemComparison comparisonDetails={data} item_name={item_name} />;
  };

  return (
    <ContainerWithTitle
      manageSectionVisibility={close_comparison}
      title="Shop Comparison"
    >
      <Card>{renderContent()}</Card>
    </ContainerWithTitle>
  );
};

export default ShopComparison;
