type Suffix = '' | 'k' | 'M' | 'B' | 'T' | 'Q';

/**
 * Shorten a number.
 *
 * For example take 1,000 and turn it into 1K, this will work all the way up to Quad
 *
 * @param value
 */
export const shortenNumber = (value: number): string => {
  if (value < 1000) {
    return value.toString();
  }

  const suffixes: Suffix[] = ['', 'k', 'M', 'B', 'T', 'Q'];
  const tier: number = Math.floor(Math.log10(value) / 3);

  if (tier >= suffixes.length) {
    throw new Error('Number exceeds supported range.');
  }

  const suffix: Suffix = suffixes[tier];
  const scaled: number = value / Math.pow(10, tier * 3);
  const formatted: string = scaled.toFixed(1).replace(/\.0$/, '');

  return `${formatted} ${suffix}`;
};

/**
 * Format numbers with a comma.
 *
 * Numbers over 1000 become 1,000
 *
 * @param value
 */
export const formatNumberWithCommas = (value: number): string => {
  if (value < 1000) {
    return value.toString();
  }

  return value.toLocaleString('en-US');
};
