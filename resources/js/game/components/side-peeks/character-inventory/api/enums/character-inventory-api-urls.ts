export enum CharacterInventoryApiUrls {
  CHARACTER_INVENTORY = '/character/{character}/inventory',
  CHARACTER_QUEST_ITEMS = '/character/{character}/quest-items',
  CHARACTER_GEM_BAG = '/character/{character}/gem-bag',
  CHARACTER_USABLE_ITEMS = '/character/{character}/usable-items',
  CHARACTER_SET_CHOICES = '/character/{character}/inventory/sets',
  CHARACTER_SET_ITEMS = '/character/{character}/inventory/set-items',
  CHARACTER_INVENTORY_ITEM = '/character/{character}/inventory/item/{item}',

  CHARACTER_SELL_SELECTED = '/character/{character}/inventory/sell-selected',
  CHARACTER_DESTROY_SELECTED = '/character/{character}/inventory/destroy-selected',
  CHARACTER_DISENCHANT_SELECTED = '/character/{character}/inventory/disenchant-selected',
}
