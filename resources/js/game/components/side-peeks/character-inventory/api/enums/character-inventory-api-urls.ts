export enum CharacterInventoryApiUrls {
  CHARACTER_INVENTORY = '/character/{character}/inventory',
  CHARACTER_QUEST_ITEMS = '/character/{character}/quest-items',
  CHARACTER_GEM_BAG = '/character/{character}/gem-bag',
  CHARACTER_USABLE_ITEMS = '/character/{character}/usable-items',
  CHARACTER_SET_CHOICES = '/character/{character}/inventory/sets',
  CHARACTER_SET_ITEMS = '/character/{character}/inventory/set-items',
  CHARACTER_INVENTORY_ITEM = '/character/{character}/inventory/item',

  CHARACTER_SELL_SELECTED = '/character/{character}/inventory/sell-selected',
  CHARACTER_DESTROY_SELECTED = '/character/{character}/inventory/destroy-selected',
  CHARACTER_DISENCHANT_SELECTED = '/character/{character}/inventory/disenchant-selected',

  CHARACTER_INVENTORY_COMPARISON = '/character/{character}/inventory/comparison',
  CHARACTER_EQUIP_ITEM = '/character/{character}/inventory/equip-item',

  CHARACTER_INVENTORY_SELL_ITEM = '/character/{character}/inventory/sell-item',
  CHARACTER_INVENTORY_DESTROY_ITEM = '/character/{character}/inventory/destroy',
  CHARACTER_INVENTORY_DISENCHANT_ITEM = '/character/{character}/inventory/disenchant-item',
}
