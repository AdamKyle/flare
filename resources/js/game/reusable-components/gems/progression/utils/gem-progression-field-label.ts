const GEM_PROGRESSION_FIELD_LABELS: Record<string, string> = {
  character_xp_bonus: 'Character XP Bonus',
  character_class_rank_xp_bonus: 'Character Class Rank XP Bonus',
  kingdom_passive_training_reduction: 'Kingdom Passive Training Reduction',
  gold_gain: 'Gold Gain',
  gold_dust_gain: 'Gold Dust Gain',
  shards_gain: 'Shards Gain',
  copper_coin_gain: 'Copper Coin Gain',
  character_class_specialty_xp_gain: 'Character Class Specialty XP Gain',
  item_drop_chance_increase: 'Item Drop Chance Increase',
  enemy_quest_item_drop_chance_increase:
    'Enemy Quest Item Drop Chance Increase',
  monster_xp_increase: 'Monster XP Increase',
  monster_gold_drop_increase: 'Monster Gold Drop Increase',
  unique: 'Unique Item Drop Chance Increase',
  mythic: 'Mythic Item Drop Chance Increase',
  cosmic: 'Cosmic Item Drop Chance Increase',
};

export const resolveGemProgressionFieldLabel = (field: string): string =>
  GEM_PROGRESSION_FIELD_LABELS[field] ?? field;
