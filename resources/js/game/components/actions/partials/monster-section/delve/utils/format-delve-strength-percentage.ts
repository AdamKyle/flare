import { formatNumberWithCommas } from 'game-utils/format-number';

export const formatDelveStrengthPercentage = (percentage: number): string =>
  `${formatNumberWithCommas(percentage)}%`;
