import LocationRequestDefinition from '../api/definitions/location-request-definition';
import LocationFormState from '../types/location-form-state';

const parseOptionalInteger = (value: string): number | null => {
  if (value.trim() === '') {
    return null;
  }

  return Number(value);
};

/**
 * Build the Location save request payload from validated wizard form state.
 */
export const buildLocationRequest = (
  state: LocationFormState
): LocationRequestDefinition => {
  return {
    name: state.name.trim(),
    description: state.description,
    quest_reward_item_id: state.quest_reward_item_id,
    required_quest_item_id: state.required_quest_item_id,
    is_port: state.is_port,
    can_players_enter: state.can_players_enter,
    can_auto_battle: state.can_auto_battle,
    x: state.x ?? 0,
    y: state.y ?? 0,
    type: state.type,
    pin_css_class: state.pin_css_class,
    hours_to_drop: parseOptionalInteger(state.hours_to_drop),
    minutes_between_delve_fights: parseOptionalInteger(
      state.minutes_between_delve_fights
    ),
  };
};
