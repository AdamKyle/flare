import GemWorldActionsProps from './gem-world-actions-props';
import { CharacterPosition } from '../../../../../map-section/api/hooks/definitions/base-map-api-definition';
import { MapMovementTypes } from '../map-movement-types/map-movement-types';

export default interface MapTabContentProps {
  character_map_position: CharacterPosition;
  can_move: boolean;
  show_timer_bar: boolean;
  length_of_time: number;
  error_message: string;
  on_close_alert: () => void;
  on_move: (amount: number, direction: MapMovementTypes) => void;
  on_teleport: () => void;
  is_set_sail_disabled: boolean;
  on_set_sail: () => void;
  on_traverse: () => void;
  is_conjure_enabled: boolean;
  on_conjure: () => void;
  is_view_location_enabled: boolean;
  on_view_location: () => void;
  on_open_kingdoms: () => void;
  gem_world_actions_props: GemWorldActionsProps;
}
