import LogEntryDefinition from '../../../api/definitions/log-entry-definition';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface LogEntrySidePeekProps extends SidePeekProps {
  entry: LogEntryDefinition;
}
