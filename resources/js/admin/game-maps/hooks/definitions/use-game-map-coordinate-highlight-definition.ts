import { Dispatch, SetStateAction } from 'react';

import CoordinateDefinition from '../../types/coordinate-definition';

export default interface UseGameMapCoordinateHighlightDefinition {
  highlighted: CoordinateDefinition | null;
  set_highlighted: Dispatch<SetStateAction<CoordinateDefinition | null>>;
}
