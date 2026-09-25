import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useId, useState } from 'react';

import ListItemOnMarketProps from './types/list-item-on-market-props';
import { useListItemOnMarket } from '../../components/market/api/hooks/use-list-item-on-market';
import CurrencyDisplay from '../currency/currency-display';
import { CurrencyDisplayMode } from '../currency/enums/currency-display-mode';
import { CurrencyType } from '../currency/enums/currency-type';
import MarketHistoryChart from '../market/market-history-chart';
import MarketPriceField from '../market/market-price-field';
import { validateMarketListingPrice } from '../market/utils/validate-market-listing-price';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';
import LoadingButton from 'ui/buttons/loading-button';

const ListItemOnMarket = ({
  type,
  on_close,
  on_action,
  slot_id,
  character_id,
  min_list_price,
}: ListItemOnMarketProps) => {
  const priceFieldId = useId();

  const {
    list_item: listItem,
    error: listItemError,
    loading: listItemLoading,
  } = useListItemOnMarket({ character_id });

  const [priceInput, setPriceInput] = useState(
    min_list_price > 0 ? `${min_list_price}` : ''
  );

  const priceValidation = validateMarketListingPrice(priceInput);
  const inputError = priceInput === '' ? null : priceValidation.error;

  const handleListItem = async () => {
    if (priceValidation.price === null) {
      return;
    }

    const result = await listItem(slot_id, priceValidation.price);

    if (!result) {
      return;
    }

    on_action(result.message);
  };

  const renderMinimumPrice = (): ReactNode => {
    if (min_list_price <= 0) {
      return null;
    }

    return (
      <p className="flex flex-wrap items-center gap-1 text-sm text-gray-700 dark:text-gray-300">
        <span>Minimum listing price:</span>
        <CurrencyDisplay
          currency={CurrencyType.GOLD}
          amount={min_list_price}
          display_mode={CurrencyDisplayMode.EXACT}
        />
      </p>
    );
  };

  const renderListingApiError = (): ReactNode => {
    if (!listItemError) {
      return null;
    }

    return <ApiErrorAlert apiError={listItemError.message} />;
  };

  return (
    <div className="flex flex-col gap-3">
      <div className="flex flex-col gap-1">
        <div className="flex items-start justify-between gap-2">
          <h2 className="text-mango-tango-600 dark:text-mango-tango-300 text-xl font-semibold">
            List on the market
          </h2>

          <LinkButton
            label="Close"
            variant={ButtonVariant.DANGER}
            on_click={on_close}
            aria_label="Close"
            additional_css="whitespace-nowrap"
          />
        </div>

        <p className="text-sm leading-relaxed text-gray-700 dark:text-gray-300">
          List your item on the market to make more gold than if you were to
          sell it to the shop. This is great for unique items, mythical items,
          cosmic items and high end enchanted items as well as alchemy items.
        </p>
      </div>

      <MarketHistoryChart item_type={type} />

      {renderListingApiError()}
      {renderMinimumPrice()}

      <MarketPriceField
        id={priceFieldId}
        label="Listing price in Gold"
        value={priceInput}
        error={inputError}
        disabled={listItemLoading}
        on_change={setPriceInput}
      />

      <LoadingButton
        label="List"
        loading_label="Listing..."
        variant={ButtonVariant.PRIMARY}
        on_click={() => void handleListItem()}
        is_loading={listItemLoading}
        disabled={priceValidation.price === null}
        aria_label="List item on the market"
      />
    </div>
  );
};

export default ListItemOnMarket;
