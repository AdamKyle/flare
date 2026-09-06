import MapGemBulkRollResultDefinition from '../../../api/definitions/map-gem-bulk-roll-result-definition';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface MapGemBulkRollResultSidePeekProps extends SidePeekProps {
  result: MapGemBulkRollResultDefinition;
}
