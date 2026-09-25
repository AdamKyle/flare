import React, { ReactNode } from 'react';

import CraftAmountPreviewProps from './types/craft-amount-preview-props';
import CurrencyDisplay from '../../../../../../../../reusable-components/currency/currency-display';
import { CurrencyDisplayMode } from '../../../../../../../../reusable-components/currency/enums/currency-display-mode';
import { CurrencyType } from '../../../../../../../../reusable-components/currency/enums/currency-type';
import { outputDestinationLabel } from '../utils/batch-crafting-labels';

import { formatNumberWithCommas } from 'game-utils/format-number';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

const renderBlocker = (blocker: string): ReactNode => (
  <li key={blocker}>{blocker}</li>
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
        <Dd>
          <CurrencyDisplay
            currency={CurrencyType.GOLD}
            amount={preview.unit_cost}
            display_mode={CurrencyDisplayMode.EXACT}
            show_label={false}
          />
        </Dd>

        <Dt>Total Cost</Dt>
        <Dd>
          <CurrencyDisplay
            currency={CurrencyType.GOLD}
            amount={preview.total_cost}
            display_mode={CurrencyDisplayMode.EXACT}
            show_label={false}
          />
        </Dd>

        <Dt>Gold Available</Dt>
        <Dd>
          <CurrencyDisplay
            currency={CurrencyType.GOLD}
            amount={preview.available_gold}
            display_mode={CurrencyDisplayMode.BALANCE}
            show_label={false}
          />
        </Dd>

        <Dt>Gold After</Dt>
        <Dd>
          <CurrencyDisplay
            currency={CurrencyType.GOLD}
            amount={preview.gold_after_purchase}
            display_mode={CurrencyDisplayMode.EXACT}
            show_label={false}
          />
        </Dd>

        <Dt>Maximum Craftable</Dt>
        <Dd>{formatNumberWithCommas(preview.maximum_request_amount)}</Dd>
      </Dl>

      {renderDestination()}

      {renderBlockers()}
    </div>
  );
};

export default CraftAmountPreview;
