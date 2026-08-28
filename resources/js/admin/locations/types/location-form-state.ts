export default interface LocationFormState {
  name: string;
  description: string;
  quest_reward_item_id: number | null;
  required_quest_item_id: number | null;
  is_port: boolean;
  can_players_enter: boolean;
  can_auto_battle: boolean;
  x: number | null;
  y: number | null;
  type: number | null;
  pin_css_class: string | null;
  hours_to_drop: string;
  minutes_between_delve_fights: string;
}
