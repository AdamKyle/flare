import LocationGemBulkRollResultDefinition from '../../../api/definitions/location-gem-bulk-roll-result-definition';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface LocationGemBulkRollResultSidePeekProps extends SidePeekProps {
  result: LocationGemBulkRollResultDefinition;
}
