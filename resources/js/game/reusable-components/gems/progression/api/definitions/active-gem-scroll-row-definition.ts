export default interface ActiveGemScrollRowDefinition {
  id: number;
  is_map_scroll: boolean;
  item_id: number;
  item_name: string;
  gem_scroll_type: string;
  gem_scroll_currency_type: string | null;
  gem_scroll_bonus: number;
  started_at: string;
  expires_at: string;
}
