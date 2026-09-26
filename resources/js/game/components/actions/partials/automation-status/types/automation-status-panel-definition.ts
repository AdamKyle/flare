import { ReactNode } from 'react';

import { AutomationStatusTab } from '../enums/automation-status-tab';

export default interface AutomationStatusPanelDefinition {
  tab: AutomationStatusTab;
  label: string;
  content: ReactNode;
  live_update_token: unknown;
}
