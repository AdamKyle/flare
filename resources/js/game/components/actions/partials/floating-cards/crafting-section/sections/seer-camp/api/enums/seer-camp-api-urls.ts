export enum SeerCampApiUrls {
  FETCH = '/visit-seer-camp/{character}',
  GEM_COMPARISON = '/gem-comparison/{character}',
  FETCH_GEMS_TO_REMOVE = '/seer-camp/gems-to-remove/{character}',
  ITEMS = '/seer-camp/{character}/items',
  GEMS = '/seer-camp/{character}/gems',
  ITEMS_WITH_GEMS = '/seer-camp/{character}/items-with-gems',
  ADD_SOCKETS = '/seer-camp/add-sockets/{character}',
  ADD_GEM = '/seer-camp/add-gem/{character}',
  REPLACE_GEM = '/seer-camp/replace-gem/{character}',
  REMOVE_GEM = '/seer-camp/remove-gem/{character}',
  REMOVE_ALL_GEMS = '/seer-camp/remove-all-gems/{character}/{inventorySlot}',
}
