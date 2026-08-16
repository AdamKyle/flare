import React, { ReactNode } from 'react';

import CraftAmountPreviewProps from './types/craft-amount-preview-props';
import { outputDestinationLabel } from '../utils/batch-crafting-labels';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';

const renderBlocker = (blocker: string, index: number): ReactNode => (
  <li key={`batch-crafting-blocker-${index}`}>{blocker}</li>
);

const CraftAmountPreview = ({
  preview,
}: CraftAmountPreviewProps): ReactNode => {
  const renderBlockers = () => {
    if (preview.blockers.length === 0) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.WARNING}>
        <ul className="list-inside list-disc">
          {preview.blockers.map(renderBlocker)}
        </ul>
      </Alert>
    );
  };

  if (!preview.item) {
    return renderBlockers();
  }

  const renderDestination = () => {
    const destinationLabel = outputDestinationLabel(preview.output_destination);

    if (!destinationLabel || !preview.destination_capacity) {
      return null;
    }

    return (
      <p>
        Destination: {destinationLabel} ({preview.destination_capacity.current}/
        {preview.destination_capacity.max} used)
      </p>
    );
  };

  return (
    <div className="space-y-2 rounded-md border border-gray-500 p-3 dark:border-gray-700">
      <p>
        <span className="font-semibold">{preview.item.name}</span>
      </p>
      <p>
        Requested amount:{' '}
        <span className="font-semibold">{preview.requested_amount}</span>
      </p>
      <p>
        Cost per item: {preview.unit_cost} gold. Total cost:{' '}
        {preview.total_cost} gold. Gold available: {preview.available_gold}.
        Gold after purchase: {preview.gold_after_purchase}.
      </p>
      <p>
        Maximum craftable amount:{' '}
        <span className="font-semibold">{preview.maximum_request_amount}</span>
      </p>
      {renderDestination()}
      {renderBlockers()}
    </div>
  );
};

export default CraftAmountPreview;
