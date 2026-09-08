export enum ClassRanksApiUrls {
  RANKS = '/class-ranks/{character}',
  SPECIALTIES = '/class-ranks/{character}/specials',
  SWITCH_CLASS = '/switch-classes/{character}/{gameClass}',
  EQUIP_SPECIALTY = '/equip-specialty/{character}/{gameClassSpecial}',
  UNEQUIP_SPECIALTY = '/unequip-specialty/{character}/{classSpecialEquipped}',
  SWAP_SPECIALTY = '/swap-specialty/{character}/{gameClassSpecial}/{classSpecialEquipped}',
}
