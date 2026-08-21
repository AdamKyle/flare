import React, { ReactNode } from 'react';

import CraftAmountPreviewProps from './types/craft-amount-preview-props';
import { outputDestinationLabel } from '../utils/batch-crafting-labels';

import { formatNumberWithCommas } from 'game-utils/format-number';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

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
      <ProgressBar
        label={destinationLabel}
        value={preview.destination_capacity.current}
        max={preview.destination_capacity.max}
        variant={ProgressBarVariant.ARTIC}
        value_label={`${formatNumberWithCommas(preview.destination_capacity.current)} / ${formatNumberWithCommas(preview.destination_capacity.max)}`}
      />
    );
  };

  return (
    <div className="space-y-3">
      <h4 className="font-semibold">{preview.item.name}</h4>

      <Dl>
        <Dt>Amount</Dt>
        <Dd>{formatNumberWithCommas(preview.requested_amount)}</Dd>

        <Dt>Cost / Item</Dt>
        <Dd>{formatNumberWithCommas(preview.unit_cost)}</Dd>

        <Dt>Total Cost</Dt>
        <Dd>{formatNumberWithCommas(preview.total_cost)}</Dd>

        <Dt>Gold Available</Dt>
        <Dd>{formatNumberWithCommas(preview.available_gold)}</Dd>

        <Dt>Gold After</Dt>
        <Dd>{formatNumberWithCommas(preview.gold_after_purchase)}</Dd>

        <Dt>Maximum Craftable</Dt>
        <Dd>{formatNumberWithCommas(preview.maximum_request_amount)}</Dd>
      </Dl>

      {renderDestination()}

      {renderBlockers()}
    </div>
  );
};

export default CraftAmountPreview;
