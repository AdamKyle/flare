import LocationDefinition from '../../../api/definitions/location-definition';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface LocationFormSidePeekProps extends SidePeekProps {
  game_map_id: number;
  location_id: number;
  on_saved: (location: LocationDefinition) => void;
}
