const GEM_SCROLL_CURRENCY_LABELS: Record<string, string> = {
  gold: 'Gold',
  gold_dust: 'Gold Dust',
  shards: 'Shards',
  copper_coins: 'Copper Coins',
};

export const resolveGemScrollCurrencyLabel = (currencyType: string): string =>
  GEM_SCROLL_CURRENCY_LABELS[currencyType] ?? currencyType;
