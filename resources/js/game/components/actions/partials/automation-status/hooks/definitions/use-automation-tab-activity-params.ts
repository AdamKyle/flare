import { AutomationStatusTab } from '../../enums/automation-status-tab';
import AutomationStatusPanelDefinition from '../../types/automation-status-panel-definition';

export default interface UseAutomationTabActivityParams {
  panels: AutomationStatusPanelDefinition[];
  active_tab: AutomationStatusTab;
}
