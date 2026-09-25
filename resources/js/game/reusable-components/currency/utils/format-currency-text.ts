import { CURRENCY_PRESENTATION } from '../constants/currency-presentation';
import { CurrencyType } from '../enums/currency-type';

import { formatNumberWithCommas } from 'game-utils/format-number';

export const formatCurrencyText = (
  currency: CurrencyType,
  amount: number
): string =>
  `${formatNumberWithCommas(amount)} ${CURRENCY_PRESENTATION[currency].label}`;
