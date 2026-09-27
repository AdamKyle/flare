import BaseUsableItemDefinition from '../../../api-definitions/items/usable-item-definitions/base-usable-item-definition';

export type CurrencyCacheTypeValue = NonNullable<
  BaseUsableItemDefinition['currency_cache_type']
>;

export const CURRENCY_CACHE_TYPE_LABELS: Record<
  CurrencyCacheTypeValue,
  string
> = {
  gold: 'Gold',
  gold_dust: 'Gold Dust',
  shards: 'Celestial Shards',
  copper_coins: 'Copper Coins',
  gold_bars: 'Gold Bars',
};
