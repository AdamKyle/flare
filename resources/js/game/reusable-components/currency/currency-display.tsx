import clsx from 'clsx';
import React, { ReactNode } from 'react';

import CurrencyBalancePopover from './components/currency-balance-popover';
import { CURRENCY_PRESENTATION } from './constants/currency-presentation';
import { CurrencyDisplayMode } from './enums/currency-display-mode';
import { useCurrencyLimit } from './hooks/use-currency-limit';
import CurrencyDisplayProps from './types/currency-display-props';

import {
  formatNumberWithCommas,
  truncateCompactNumber,
} from 'game-utils/format-number';

import GeneralToolTip from 'ui/tool-tips/general-tool-tip';

const CurrencyDisplay = ({
  currency,
  amount,
  display_mode,
  label,
  show_label = true,
  additional_css,
}: CurrencyDisplayProps): ReactNode => {
  const limit = useCurrencyLimit(currency);

  const presentation = CURRENCY_PRESENTATION[currency];
  const visibleLabel = label ?? presentation.label;
  const exactAmount = formatNumberWithCommas(amount);
  const exactAmountWithCurrency = `${exactAmount} ${presentation.label}`;
  const hasContextLabel = visibleLabel !== presentation.label;
  const accessibleText = hasContextLabel
    ? `${visibleLabel}: ${exactAmountWithCurrency}`
    : exactAmountWithCurrency;

  const renderIcon = (): ReactNode => (
    <i
      className={clsx(presentation.icon_class, presentation.color_class)}
      aria-hidden="true"
    />
  );

  const renderLabel = (): ReactNode => {
    if (!show_label) {
      return null;
    }

    return <span>{visibleLabel}</span>;
  };

  const renderScreenReaderLabel = (): ReactNode => {
    if (show_label) {
      return null;
    }

    return <span className="sr-only">{visibleLabel}</span>;
  };

  const renderExact = (): ReactNode => (
    <span className={clsx('inline-flex items-center gap-1', additional_css)}>
      {renderIcon()}
      <span>{exactAmount}</span>
      {renderLabel()}
      {renderScreenReaderLabel()}
    </span>
  );

  const renderBalance = (): ReactNode => (
    <GeneralToolTip
      label={visibleLabel}
      message={
        <CurrencyBalancePopover
          presentation={presentation}
          amount={amount}
          limit={limit}
        />
      }
      placement="above"
      trigger_aria_label={accessibleText}
      trigger={
        <span
          className={clsx('inline-flex items-center gap-1', additional_css)}
          aria-hidden="true"
        >
          {renderIcon()}
          <span>{truncateCompactNumber(amount)}</span>
          {renderLabel()}
        </span>
      }
    />
  );

  if (display_mode === CurrencyDisplayMode.BALANCE) {
    return renderBalance();
  }

  return renderExact();
};

export default CurrencyDisplay;
