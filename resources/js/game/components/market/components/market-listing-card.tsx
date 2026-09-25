import clsx from 'clsx';
import { formatDistanceToNowStrict } from 'date-fns';
import React, { ReactNode } from 'react';

import MarketListingCardProps from './types/market-listing-card-props';
import CurrencyDisplay from '../../../reusable-components/currency/currency-display';
import { CurrencyDisplayMode } from '../../../reusable-components/currency/enums/currency-display-mode';
import { CurrencyType } from '../../../reusable-components/currency/enums/currency-type';
import { planeTextItemColors } from '../../character-sheet/partials/character-inventory/styles/backpack-item-styles';
import { resolveMarketItemTypeLabel } from '../constants/market-item-type-labels';

const MarketListingCard = ({
  listing,
  actions,
  status,
}: MarketListingCardProps): ReactNode => {
  const renderStatus = (): ReactNode => {
    if (!status) {
      return null;
    }

    return (
      <p className="text-sm font-semibold text-gray-700 dark:text-gray-300">
        {status}
      </p>
    );
  };

  return (
    <article
      aria-label={listing.name}
      className="space-y-2 rounded-md border border-gray-300 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
    >
      <h3
        className={clsx(
          'text-lg font-semibold break-words',
          planeTextItemColors(listing.item)
        )}
      >
        {listing.name}
      </h3>
      <dl className="grid grid-cols-2 gap-x-3 gap-y-1 text-sm text-gray-800 dark:text-gray-200">
        <dt className="text-gray-600 dark:text-gray-400">Type</dt>
        <dd>{resolveMarketItemTypeLabel(listing.type)}</dd>
        <dt className="text-gray-600 dark:text-gray-400">Seller</dt>
        <dd className="break-words">{listing.character_name}</dd>
        <dt className="text-gray-600 dark:text-gray-400">Price</dt>
        <dd>
          <CurrencyDisplay
            currency={CurrencyType.GOLD}
            amount={listing.listed_price}
            display_mode={CurrencyDisplayMode.EXACT}
            additional_css="font-semibold"
          />
        </dd>
        <dt className="text-gray-600 dark:text-gray-400">Listed</dt>
        <dd>
          <time dateTime={listing.listed_at}>
            {formatDistanceToNowStrict(new Date(listing.listed_at), {
              addSuffix: true,
            })}
          </time>
        </dd>
      </dl>
      {renderStatus()}
      <div className="flex flex-wrap gap-2">{actions}</div>
    </article>
  );
};

export default MarketListingCard;
