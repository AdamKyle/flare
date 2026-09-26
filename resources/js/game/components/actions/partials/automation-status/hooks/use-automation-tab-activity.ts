import { useCallback, useEffect, useRef, useState } from 'react';

import UseAutomationTabActivityDefinition from './definitions/use-automation-tab-activity-definition';
import UseAutomationTabActivityParams from './definitions/use-automation-tab-activity-params';
import { AutomationStatusTab } from '../enums/automation-status-tab';

/**
 * Each panel's `live_update_token` only changes when its websocket delivers
 * an update, so a token change on an inactive tab means "changed since
 * viewed"; the first observed token (REST hydration) never marks a tab.
 */
export const useAutomationTabActivity = ({
  panels,
  active_tab: activeTab,
}: UseAutomationTabActivityParams): UseAutomationTabActivityDefinition => {
  const [changedTabs, setChangedTabs] = useState<AutomationStatusTab[]>([]);

  const previousTokensRef = useRef<Map<AutomationStatusTab, unknown>>(
    new Map()
  );

  useEffect(() => {
    const previousTokens = previousTokensRef.current;

    const updatedTabs = panels
      .filter(
        (panel) =>
          panel.tab !== activeTab &&
          previousTokens.has(panel.tab) &&
          previousTokens.get(panel.tab) !== panel.live_update_token
      )
      .map((panel) => panel.tab);

    previousTokensRef.current = new Map(
      panels.map((panel): [AutomationStatusTab, unknown] => [
        panel.tab,
        panel.live_update_token,
      ])
    );

    if (updatedTabs.length === 0) {
      return;
    }

    setChangedTabs((currentTabs) => [
      ...currentTabs,
      ...updatedTabs.filter((tab) => !currentTabs.includes(tab)),
    ]);
  }, [panels, activeTab]);

  const markViewed = useCallback((tab: AutomationStatusTab) => {
    setChangedTabs((currentTabs) =>
      currentTabs.filter((changedTab) => changedTab !== tab)
    );
  }, []);

  return { changed_tabs: changedTabs, mark_viewed: markViewed };
};
