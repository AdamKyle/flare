import AutomationStatusPanelDefinition from './automation-status-panel-definition';

export default interface AutomationStatusProps {
  primary_panel: AutomationStatusPanelDefinition;
  batch_crafting_panel: AutomationStatusPanelDefinition | null;
}
