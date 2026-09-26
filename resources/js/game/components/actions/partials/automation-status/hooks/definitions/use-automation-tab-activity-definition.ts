import { AutomationStatusTab } from '../../enums/automation-status-tab';

export default interface UseAutomationTabActivityDefinition {
  changed_tabs: AutomationStatusTab[];
  mark_viewed: (tab: AutomationStatusTab) => void;
}
