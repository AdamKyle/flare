import SystemBugReportDefinition from '../../../api/definitions/system-bug-report-definition';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface BugReportSidePeekProps extends SidePeekProps {
  bug: SystemBugReportDefinition;
}
