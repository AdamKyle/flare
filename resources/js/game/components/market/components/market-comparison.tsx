import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import MarketComparisonProps from './types/market-comparison-props';
import { useCustomContext } from '../../../../utils/hooks/use-custom-context';
import CurrencyDisplay from '../../../reusable-components/currency/currency-display';
import { CurrencyDisplayMode } from '../../../reusable-components/currency/enums/currency-display-mode';
import { CurrencyType } from '../../../reusable-components/currency/enums/currency-type';
import EquipItemSelectionDefinition from '../../../reusable-components/item/definitions/equip-item-selection-definition';
import ItemComparison from '../../../reusable-components/item/item-comparison';
import MarketPurchaseResponseDefinition from '../api/definitions/market-purchase-response-definition';
import { useBuyAndReplaceMarketListing } from '../api/hooks/use-buy-and-replace-market-listing';
import { useBuyMarketListing } from '../api/hooks/use-buy-market-listing';
import { useCompareMarketListing } from '../api/hooks/use-compare-market-listing';
import { MarketContext } from '../context/market-context';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LoadingButton from 'ui/buttons/loading-button';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const MarketComparison = ({
  listing_id,
  listing_name,
  on_close,
  on_purchased,
}: MarketComparisonProps): ReactNode => {
  const {
    character_id: characterId,
    is_listing_available: isListingAvailable,
    update_character: updateCharacter,
  } = useCustomContext(MarketContext, 'MarketComparison');

  const { data, loading, error } = useCompareMarketListing(
    listing_id,
    characterId
  );
  const {
    buy,
    loading: isBuying,
    error: buyError,
  } = useBuyMarketListing(characterId);
  const {
    buy_and_replace: buyAndReplace,
    loading: isBuyingAndReplacing,
    error: buyAndReplaceError,
  } = useBuyAndReplaceMarketListing(characterId);

  const isAvailable = isListingAvailable(listing_id);
  const isPurchasing = isBuying || isBuyingAndReplacing;

  const handlePurchaseResult = (
    result: MarketPurchaseResponseDefinition | null
  ) => {
    if (!result) {
      return;
    }

    updateCharacter({
      gold: result.gold,
      inventory_count: result.inventory_count,
    });

    on_purchased(result.message);
  };

  const handleBuy = async () => {
    handlePurchaseResult(await buy(listing_id));
  };

  const handleBuyAndReplace = async (
    selection: EquipItemSelectionDefinition
  ) => {
    handlePurchaseResult(
      await buyAndReplace(listing_id, {
        position: selection.position,
        slot_id: selection.slot_id,
        equip_type: selection.equip_type,
      })
    );
  };

  const renderUnavailable = (): ReactNode => {
    if (isAvailable) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.WARNING}>
        This listing is no longer available. It may have been sold, delisted or
        is being edited by its seller.
      </Alert>
    );
  };

  const renderBuyError = (): ReactNode => {
    if (!buyError) {
      return null;
    }

    return <ApiErrorAlert apiError={buyError.message} />;
  };

  const renderComparison = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error) {
      return <ApiErrorAlert apiError={error.message} />;
    }

    if (!data) {
      return null;
    }

    return (
      <div className="space-y-4">
        <dl className="grid grid-cols-2 gap-x-3 gap-y-1 text-sm">
          <dt className="text-gray-600 dark:text-gray-400">Listed price</dt>
          <dd>
            <CurrencyDisplay
              currency={CurrencyType.GOLD}
              amount={data.listed_price}
              display_mode={CurrencyDisplayMode.EXACT}
            />
          </dd>
          <dt className="text-gray-600 dark:text-gray-400">
            Total with 5% tax
          </dt>
          <dd>
            <CurrencyDisplay
              currency={CurrencyType.GOLD}
              amount={data.total_price}
              display_mode={CurrencyDisplayMode.EXACT}
              additional_css="font-semibold"
            />
          </dd>
        </dl>
        {renderBuyError()}
        <LoadingButton
          label="Buy"
          loading_label="Buying..."
          variant={ButtonVariant.PRIMARY}
          on_click={() => void handleBuy()}
          is_loading={isBuying}
          disabled={!isAvailable || isPurchasing}
          aria_label={`Buy ${listing_name}`}
        />
        <ItemComparison
          comparisonDetails={data}
          is_purchasing={isBuyingAndReplacing}
          error_message={buyAndReplaceError}
          on_buy_and_replace={(selection) =>
            void handleBuyAndReplace(selection)
          }
          show_buy_and_replace={isAvailable && !isBuying}
        />
      </div>
    );
  };

  return (
    <StackedCard on_close={on_close} aria_label={`Compare ${listing_name}`}>
      <div className="space-y-4 text-gray-800 dark:text-gray-200">
        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
          {listing_name}
        </h2>
        {renderUnavailable()}
        {renderComparison()}
      </div>
    </StackedCard>
  );
};

export default MarketComparison;
