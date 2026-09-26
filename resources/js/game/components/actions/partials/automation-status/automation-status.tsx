import React, { ReactNode, useState } from 'react';

import AutomationStatusTabPanel from './components/automation-status-tab-panel';
import AutomationStatusTabPanelProps from './components/types/automation-status-tab-panel-props';
import { useAutomationTabActivity } from './hooks/use-automation-tab-activity';
import AutomationStatusPanelDefinition from './types/automation-status-panel-definition';
import AutomationStatusProps from './types/automation-status-props';

import PillTabs from 'ui/tabs/pill-tabs';
import { TabTupleFromProps } from 'ui/tabs/types/tab-item';

const ACTIVITY_ICON = 'far fa-bell';
const ACTIVITY_ICON_STYLES = 'text-mango-tango-600 dark:text-mango-tango-300';

const AutomationStatus = ({
  primary_panel: primaryPanel,
  batch_crafting_panel: batchCraftingPanel,
}: AutomationStatusProps): ReactNode => {
  const [activeTab, setActiveTab] = useState(primaryPanel.tab);

  const panels: AutomationStatusPanelDefinition[] =
    batchCraftingPanel === null
      ? [primaryPanel]
      : [primaryPanel, batchCraftingPanel];

  const { changed_tabs: changedTabs, mark_viewed: markViewed } =
    useAutomationTabActivity({ panels, active_tab: activeTab });

  const matchedActiveIndex = panels.findIndex(
    (panel) => panel.tab === activeTab
  );
  const activeIndex = matchedActiveIndex === -1 ? 0 : matchedActiveIndex;

  const handleActiveIndexChange = (index: number) => {
    const selectedPanel = panels[index];

    if (!selectedPanel) {
      return;
    }

    setActiveTab(selectedPanel.tab);
    markViewed(selectedPanel.tab);
  };

  const buildTab = (panel: AutomationStatusPanelDefinition) => {
    const isChanged = changedTabs.includes(panel.tab);

    return {
      label: panel.label,
      component: AutomationStatusTabPanel,
      props: { content: panel.content },
      activity_icon: isChanged ? ACTIVITY_ICON : undefined,
      icon_styles: isChanged ? ACTIVITY_ICON_STYLES : undefined,
    };
  };

  if (batchCraftingPanel === null) {
    return primaryPanel.content;
  }

  const tabs: Readonly<
    TabTupleFromProps<
      [AutomationStatusTabPanelProps, AutomationStatusTabPanelProps]
    >
  > = [buildTab(primaryPanel), buildTab(batchCraftingPanel)];

  return (
    <PillTabs
      tabs={tabs}
      ariaLabel="Running automations"
      activeIndex={activeIndex}
      onActiveIndexChange={handleActiveIndexChange}
      keep_panels_mounted
    />
  );
};

export default AutomationStatus;
