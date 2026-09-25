import clsx from 'clsx';
import React, { ReactNode } from 'react';

import MarketPriceFieldProps from './types/market-price-field-props';
import { validateMarketListingPrice } from './utils/validate-market-listing-price';
import { CURRENCY_PRESENTATION } from '../currency/constants/currency-presentation';
import CurrencyDisplay from '../currency/currency-display';
import { CurrencyDisplayMode } from '../currency/enums/currency-display-mode';
import { CurrencyType } from '../currency/enums/currency-type';

import Input from 'ui/input/input';

const MarketPriceField = ({
  id,
  label,
  value,
  error,
  disabled,
  on_change,
}: MarketPriceFieldProps): ReactNode => {
  const goldPresentation = CURRENCY_PRESENTATION[CurrencyType.GOLD];
  const errorId = `${id}-error`;
  const parsedPrice = validateMarketListingPrice(value).price;

  const renderError = (): ReactNode => {
    if (!error) {
      return null;
    }

    return (
      <p
        id={errorId}
        role="alert"
        className="mt-1 text-xs text-rose-600 dark:text-rose-400"
      >
        {error}
      </p>
    );
  };

  const renderPricePreview = (): ReactNode => {
    if (parsedPrice === null) {
      return null;
    }

    return (
      <p className="mt-1 flex flex-wrap items-center gap-1 text-sm text-gray-700 dark:text-gray-300">
        <span>Listing price:</span>
        <CurrencyDisplay
          currency={CurrencyType.GOLD}
          amount={parsedPrice}
          display_mode={CurrencyDisplayMode.EXACT}
        />
      </p>
    );
  };

  return (
    <div className="w-full">
      <label
        htmlFor={id}
        className="mb-1 flex items-center gap-1 text-sm font-medium text-gray-800 dark:text-gray-200"
      >
        <span>{label}</span>
        <i
          className={clsx(
            goldPresentation.icon_class,
            goldPresentation.color_class
          )}
          aria-hidden="true"
        />
      </label>
      <Input
        id={id}
        on_change={on_change}
        value={value}
        place_holder="Enter a price in Gold"
        disabled={disabled}
        described_by={error ? errorId : undefined}
        invalid={Boolean(error)}
        required
        clearable
      />
      {renderError()}
      {renderPricePreview()}
    </div>
  );
};

export default MarketPriceField;
