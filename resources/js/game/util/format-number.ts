/**
 * Letter suffixes used by {@link shortenNumber} for compact notation.
 *
 * - `k` — thousands
 * - `M` — millions
 * - `B` — billions
 * - `T` — trillions
 * - `Q` — quadrillions
 */
export type Suffix = '' | 'k' | 'M' | 'B' | 'T' | 'Q';

/**
 * Shortens a positive integer into a compact string with a suffix.
 *
 * Uses base-10 (powers of 1,000) thresholds:
 * - 1,000 → `1 k`
 * - 1,000,000 → `1 M`
 * - 1,000,000,000 → `1 B`
 * - 1,000,000,000,000 → `1 T`
 * - 1,000,000,000,000,000 → `1 Q`
 *
 * Values below 1,000 are returned as-is.
 * Values above the largest supported tier throw an error.
 *
 * @param {number} value - The number to shorten. Should be `>= 0`.
 * @returns {string} The shortened string with at most one decimal place and a suffix.
 * @throws {Error} If `value` exceeds the supported tier range.
 *
 * @example
 * shortenNumber(999);        // "999"
 * @example
 * shortenNumber(1_532);      // "1.5 k"
 * @example
 * shortenNumber(15_320_000); // "15.3 M"
 * @example
 * shortenNumber(1_000_000_000); // "1 B"
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
 * Formats a number using US thousands separators.
 *
 * Values below 1,000 are returned as a plain string without commas.
 *
 * @param {number} value - The number to format.
 * @returns {string} The number formatted with commas (e.g., `12,345`).
 *
 * @example
 * formatNumberWithCommas(999);     // "999"
 * @example
 * formatNumberWithCommas(12345);   // "12,345"
 * @example
 * formatNumberWithCommas(1234567); // "1,234,567"
 */
export const formatNumberWithCommas = (value: number): string => {
  if (value < 1000) {
    return value.toString();
  }

  return value.toLocaleString('en-US');
};

/**
 * Formats a proportion as a percentage with two decimal places.
 *
 * Pass values like `0.55` to represent `55%`.
 * Negative values are supported.
 *
 * @param {number} value - The proportion to convert (e.g., `0.1234` for 12.34%).
 * @returns {string} The formatted percentage (e.g., `"12.34%"`).
 *
 * @example
 * formatPercent(0.55);  // "55.00%"
 * @example
 * formatPercent(1);     // "100.00%"
 * @example
 * formatPercent(-0.1);  // "-10.00%"
 */
export const formatPercent = (value: number): string => {
  return `${(Number(value) * 100).toFixed(2)}%`;
};

/**
 * Formats a proportion as a signed percentage with two decimal places.
 *
 * - Adds a leading `+` for positive values and `-` for negative values.
 * - Uses the absolute value for the numeric part so the sign appears only once.
 *
 * @param {number} value - The proportion to convert (e.g., `0.075` for +7.50%).
 * @returns {string} The signed percentage (e.g., `"+7.50%"`, "-3.25%", or "0.00%").
 *
 * @example
 * formatSignedPercent(0.075); // "+7.50%"
 * @example
 * formatSignedPercent(-0.0325); // "-3.25%"
 * @example
 * formatSignedPercent(0); // "0.00%"
 */
export const formatSignedPercent = (value: number): string => {
  const pct = Math.abs(value * 100).toFixed(2);

  let sign: '' | '+' | '-' = '';
  if (value > 0) {
    sign = '+';
  }
  if (value < 0) {
    sign = '-';
  }

  return `${sign}${pct}%`;
};

/**
 * Formats an integer with an explicit `+` or `-` sign and thousands separators.
 *
 * - Returns `"0"` for zero.
 * - Uses {@link formatNumberWithCommas} for the absolute value.
 *
 * @param {number} value - The integer to format (positive or negative).
 * @returns {string} The signed, comma-formatted integer (e.g., `"+12,345"` or `"-8,000"`).
 *
 * @example
 * formatIntWithPlus(0);       // "0"
 * @example
 * formatIntWithPlus(12345);   // "+12,345"
 * @example
 * formatIntWithPlus(-8000);   // "-8,000"
 */
export const formatIntWithPlus = (value: number): string => {
  if (value === 0) {
    return '0';
  }

  const sign = value > 0 ? '+' : '-';
  const abs = Math.abs(value);

  return `${sign}${formatNumberWithCommas(abs)}`;
};

/**
 * Formats a number with up to two fractional digits using US locale.
 *
 * Useful for displaying small magnitudes without trailing zeros explosion.
 *
 * @param {number} value - The number to format.
 * @returns {string} The number formatted with at most two decimals.
 *
 * @example
 * formatFloat(12);        // "12"
 * @example
 * formatFloat(12.3);      // "12.3"
 * @example
 * formatFloat(12.3456);   // "12.35"
 */
export const formatFloat = (value: number): string => {
  return Number(value).toLocaleString('en-US', { maximumFractionDigits: 2 });
};
