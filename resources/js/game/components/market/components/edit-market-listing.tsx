import ApiErrorAlert from 'api-handler/components/api-error-alert';
import clsx from 'clsx';
import React, { ReactNode, useId, useState } from 'react';

import EditMarketListingProps from './types/edit-market-listing-props';
import CurrencyDisplay from '../../../reusable-components/currency/currency-display';
import { CurrencyDisplayMode } from '../../../reusable-components/currency/enums/currency-display-mode';
import { CurrencyType } from '../../../reusable-components/currency/enums/currency-type';
import MarketHistoryChart from '../../../reusable-components/market/market-history-chart';
import MarketPriceField from '../../../reusable-components/market/market-price-field';
import { validateMarketListingPrice } from '../../../reusable-components/market/utils/validate-market-listing-price';
import { planeTextItemColors } from '../../character-sheet/partials/character-inventory/styles/backpack-item-styles';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LoadingButton from 'ui/buttons/loading-button';
import StackedCard from 'ui/cards/stacked-card';

const EditMarketListing = ({
  listing,
  is_processing,
  error,
  on_save,
  on_delist,
  on_close,
}: EditMarketListingProps): ReactNode => {
  const priceFieldId = useId();

  const [priceInput, setPriceInput] = useState(`${listing.listed_price}`);

  const priceValidation = validateMarketListingPrice(priceInput);

  const handleSave = () => {
    if (priceValidation.price === null) {
      return;
    }

    on_save(priceValidation.price);
  };

  const renderError = (): ReactNode => {
    if (!error) {
      return null;
    }

    return <ApiErrorAlert apiError={error.message} />;
  };

  return (
    <StackedCard on_close={on_close} aria_label={`Edit ${listing.name}`}>
      <div className="space-y-4 text-gray-800 dark:text-gray-200">
        <h2
          className={clsx(
            'text-lg font-semibold',
            planeTextItemColors(listing.item)
          )}
        >
          {listing.name}
        </h2>
        <p className="flex flex-wrap items-center gap-1 text-sm">
          <span>Current price:</span>
          <CurrencyDisplay
            currency={CurrencyType.GOLD}
            amount={listing.listed_price}
            display_mode={CurrencyDisplayMode.EXACT}
            additional_css="font-semibold"
          />
        </p>
        <p className="text-sm text-gray-700 dark:text-gray-300">
          This listing is hidden from other players while you edit it. Closing
          this panel makes it available again at its current price.
        </p>
        <MarketHistoryChart item_type={listing.type} />
        {renderError()}
        <MarketPriceField
          id={priceFieldId}
          label="New listing price in Gold"
          value={priceInput}
          error={priceInput === '' ? null : priceValidation.error}
          disabled={is_processing}
          on_change={setPriceInput}
        />
        <div className="flex flex-col gap-2 sm:flex-row">
          <LoadingButton
            label="Update Listed Price"
            loading_label="Saving..."
            variant={ButtonVariant.PRIMARY}
            on_click={handleSave}
            is_loading={is_processing}
            disabled={priceValidation.price === null}
            additional_css="w-full sm:w-auto"
          />
          <LoadingButton
            label="Delist"
            loading_label="Delisting..."
            variant={ButtonVariant.DANGER}
            on_click={on_delist}
            is_loading={is_processing}
            additional_css="w-full sm:w-auto"
            aria_label={`Delist ${listing.name}`}
          />
        </div>
      </div>
    </StackedCard>
  );
};

export default EditMarketListing;
