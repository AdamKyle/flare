import { CurrencyType } from '../../../../../../reusable-components/currency/enums/currency-type';

const EXPLORATION_CURRENCY_LABELS: Record<string, string> = {
  gold: 'Gold',
  gold_dust: 'Gold Dust',
  shards: 'Shards',
  copper_coins: 'Copper Coins',
  levels_gained: 'Levels Gained',
};

const EXPLORATION_CURRENCY_TYPES: Record<string, CurrencyType> = {
  gold: CurrencyType.GOLD,
  gold_dust: CurrencyType.GOLD_DUST,
  shards: CurrencyType.SHARDS,
  copper_coins: CurrencyType.COPPER_COINS,
};

/**
 * `currencies_gained` also carries `healing_done`/`damage_blocked`, which
 * the panel already shows via the dedicated `healing`/`blocked` fields —
 * excluded here so they are not displayed twice.
 */
const EXCLUDED_CURRENCY_KEYS = new Set(['healing_done', 'damage_blocked']);

export const resolveExplorationCurrencyLabel = (currencyType: string): string =>
  EXPLORATION_CURRENCY_LABELS[currencyType] ?? currencyType;

export const resolveExplorationCurrencyType = (
  currencyType: string
): CurrencyType | null => EXPLORATION_CURRENCY_TYPES[currencyType] ?? null;

export const isDisplayableExplorationCurrency = (
  currencyType: string
): boolean => !EXCLUDED_CURRENCY_KEYS.has(currencyType);
