import LocationDefinition from '../api/definitions/location-definition';

export default interface LocationFormScreenProps {
  game_map_id: number;
  location_id: number | null;
  initial_x: number | null;
  initial_y: number | null;
  on_saved: (location: LocationDefinition) => void;
  on_cancel: () => void;
  embedded?: boolean;
}
