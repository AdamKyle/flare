export enum MarketApis {
  MARKET_ACCESS = '/market-board/access/{character}',
  MARKET_HISTORY_FOR_TYPE = '/market-history/fetch-history-for-type',
  LIST_ITEM_ON_MARKET = '/market-board/sell-item/{character}',
  LISTINGS = '/market-board/items',
  COMPARE_LISTING = '/market-board/items/{marketBoard}/compare/{character}',
  BUY_LISTING = '/market-board/items/{marketBoard}/buy/{character}',
  BUY_AND_REPLACE_LISTING = '/market-board/items/{marketBoard}/buy-and-replace/{character}',
  CURRENT_LISTINGS = '/market-board/current-listings/{character}',
  BEGIN_LISTING_EDIT = '/market-board/current-listings/{marketBoard}/edit/{character}',
  OWNED_LISTING = '/market-board/current-listings/{marketBoard}/{character}',
  CANCEL_LISTING_EDIT = '/market-board/current-listings/{marketBoard}/cancel-edit/{character}',
}
