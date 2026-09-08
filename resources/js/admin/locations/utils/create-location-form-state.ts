import LocationDefinition from '../api/definitions/location-definition';
import LocationFormState from '../types/location-form-state';

const numberToString = (value: number | null): string =>
  value === null ? '' : String(value);

export const createLocationFormState = (
  location: LocationDefinition | null,
  initialX: number | null,
  initialY: number | null
): LocationFormState => {
  if (location) {
    return {
      name: location.name,
      description: location.description,
      quest_reward_item_id: location.quest_reward_item_id,
      required_quest_item_id: location.required_quest_item_id,
      is_port: location.is_port,
      can_players_enter: location.can_players_enter,
      can_auto_battle: location.can_auto_battle,
      x: location.x,
      y: location.y,
      type: location.type,
      pin_css_class: location.pin_css_class,
      hours_to_drop: numberToString(location.hours_to_drop),
      minutes_between_delve_fights: numberToString(
        location.minutes_between_delve_fights
      ),
    };
  }

  return {
    name: '',
    description: '',
    quest_reward_item_id: null,
    required_quest_item_id: null,
    is_port: false,
    can_players_enter: true,
    can_auto_battle: true,
    x: initialX,
    y: initialY,
    type: null,
    pin_css_class: null,
    hours_to_drop: '',
    minutes_between_delve_fights: '',
  };
};
