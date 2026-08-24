import React, { ReactNode } from 'react';

import BatchCraftingOutputSummaryProps from './types/batch-crafting-output-summary-props';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingOutputDestination } from '../enums/batch-crafting-output-destination';
import {
  dispositionLabel,
  outputDestinationLabel,
} from '../utils/batch-crafting-labels';

const BatchCraftingOutputSummary = ({
  title,
  disposition,
  output_destination,
  output_set_name,
  listing_price,
}: BatchCraftingOutputSummaryProps): ReactNode => {
  const destinationSummaryValue =
    output_destination === BatchCraftingOutputDestination.INVENTORY_SET
      ? output_set_name
      : outputDestinationLabel(output_destination);

  return (
    <div className="space-y-2 rounded-md border border-gray-300 p-3 dark:border-gray-700">
      <h4 className="text-sm font-semibold text-gray-900 dark:text-gray-100">
        {title}
      </h4>
      <dl className="grid grid-cols-2 gap-x-3 gap-y-1 text-sm">
        <dt className="text-gray-600 dark:text-gray-400">Action</dt>
        <dd>{dispositionLabel(disposition)}</dd>
        {disposition === BatchCraftingDisposition.KEEP && (
          <>
            <dt className="text-gray-600 dark:text-gray-400">Destination</dt>
            <dd>{destinationSummaryValue}</dd>
          </>
        )}
        {disposition === BatchCraftingDisposition.LIST &&
          listing_price != null && (
            <>
              <dt className="text-gray-600 dark:text-gray-400">
                Listing Price
              </dt>
              <dd>{listing_price.toLocaleString()}</dd>
            </>
          )}
      </dl>
    </div>
  );
};

export default BatchCraftingOutputSummary;
