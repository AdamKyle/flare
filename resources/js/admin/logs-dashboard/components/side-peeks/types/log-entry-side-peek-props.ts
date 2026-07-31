import LogEntryDetailDefinition from '../../../api/definitions/log-entry-detail-definition';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface LogEntrySidePeekProps extends SidePeekProps {
  entry: LogEntryDetailDefinition;
}
