export enum GemScrollManagementApiUrls {
  USE_SCROLL = '/character/{character}/gem-scrolls/use/{alchemyBagSlot}',
  FILL_MAP_SCROLL = '/character/{character}/gem-scrolls/map/{characterGameMapGemScroll}/fill/{alchemyBagSlot}',
  FILL_LOCATION_SCROLL = '/character/{character}/gem-scrolls/location/{characterGameLocationGemScroll}/fill/{alchemyBagSlot}',
  REMOVE_MAP_SCROLL = '/character/{character}/gem-scrolls/map/{characterGameMapGemScroll}/remove',
  REMOVE_LOCATION_SCROLL = '/character/{character}/gem-scrolls/location/{characterGameLocationGemScroll}/remove',
}
