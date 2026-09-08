export interface QuestBrowseOptionsGameMapDefinition {
  id: number;
  name: string;
}

export default interface QuestBrowseOptionsDefinition {
  default_game_map_id: number | null;
  game_maps: QuestBrowseOptionsGameMapDefinition[];
  active_raid_map_ids?: number[];
}
