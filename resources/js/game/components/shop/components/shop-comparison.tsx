import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { isNil } from 'lodash';
import React from 'react';

import EquipItemSelectionDefinition from '../../../reusable-components/item/definitions/equip-item-selection-definition';
import ItemComparison from '../../../reusable-components/item/item-comparison';
import { ShopApiUrls } from '../api/enums/shop-api-urls';
import { useCompareItemApi } from '../api/hooks/use-compare-item-api';
import { usePurchaseAndReplaceApi } from '../api/hooks/use-purchase-and-replace-api';
import ComparisonProps from '../types/comparison-props';

import { GameDataError } from 'game-data/components/game-data-error';
import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const ShopComparison = ({
  item_name,
  item_type,
  close_comparison,
  on_purchase_and_replace_success,
}: ComparisonProps) => {
  const { gameData } = useGameData();

  const { loading, error, data } = useCompareItemApi({
    characterData: gameData?.character,
    item_name,
    item_type,
    url: ShopApiUrls.COMPARE_ITEMS,
  });

  const {
    loading: isPurchasing,
    error: purchaseAndReplaceError,
    mutate: purchaseAndReplace,
  } = usePurchaseAndReplaceApi({
    character_id: gameData?.character?.id ?? 0,
    on_success: on_purchase_and_replace_success,
  });

  const handleBuyAndReplace = (selection: EquipItemSelectionDefinition) => {
    void purchaseAndReplace({
      position: selection.position,
      slot_id: selection.slot_id,
      equip_type: selection.equip_type,
      item_id_to_buy: selection.item_id,
    });
  };

  const renderContent = () => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (!isNil(error)) {
      return <ApiErrorAlert apiError={error.message} />;
    }

    if (isNil(data)) {
      return <GameDataError />;
    }

    return (
      <ItemComparison
        comparisonDetails={data}
        is_purchasing={isPurchasing}
        error_message={purchaseAndReplaceError}
        on_buy_and_replace={handleBuyAndReplace}
        show_buy_and_replace
      />
    );
  };

  return (
    <ContainerWithTitle
      manageSectionVisibility={close_comparison}
      title="Shop Comparison"
    >
      <Card>
        <Alert variant={AlertVariant.INFO}>
          If the item your looking at is better click "Buy and replace". This
          will allow you to decide which slot to equip it in, and it will
          replace that item, even if the item is inside an equipped set. The
          item you replace, will placed back into your inventory, assuming you
          have the space. Should you not have the space, you will not be able to
          purchase and thus replace.
        </Alert>
        {renderContent()}
      </Card>
    </ContainerWithTitle>
  );
};

export default ShopComparison;
