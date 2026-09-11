import GemScrollFamily from '../../types/gem-scroll-family';

export default interface ActiveGemScrollRowDefinition {
  id: number;
  is_map_scroll: boolean;
  profile_id: number;
  profile_name: string | null;
  map_name: string | null;
  generated_game_map_name: string | null;
  item_id: number;
  item_name: string;
  gem_scroll_type: GemScrollFamily;
  gem_scroll_currency_type: string | null;
  gem_scroll_bonus: number;
  gem_scroll_socket_chance: number | null;
  gem_scroll_pre_gem_chance: number | null;
  started_at: string;
  expires_at: string;
  is_current_profile: boolean;
}
