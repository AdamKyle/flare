export default interface GemProfileParticipationRowDefinition {
  is_map_profile: boolean;
  profile_id: number;
  profile_name: string | null;
  map_name: string | null;
  generated_game_map_name: string | null;
  personal_level: number;
  personal_xp: number;
  global_level: number;
  global_xp: number;
  is_current_profile: boolean;
}
