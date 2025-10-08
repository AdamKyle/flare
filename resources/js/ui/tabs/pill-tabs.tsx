import React, { useId, useMemo, useState } from 'react';

import TabsList from 'ui/tabs/tabs-list';
import TabsPanels from 'ui/tabs/tabs-panels';
import PillTabsProps from 'ui/tabs/types/pill-tabs-props';

const PillTabs = <Cs extends readonly ((props: object) => React.ReactNode)[]>({
  tabs,
  ariaLabel = 'Tabs',
  initialIndex = 0,
}: PillTabsProps<Cs>) => {
  const [activeIndex, setActiveIndex] = useState<number>(initialIndex);

  const groupId = useId();

  const tabIds = useMemo(() => {
    return Array.from(tabs.keys()).map(
      (tabIndex: number): string => `${groupId}-tab-${tabIndex}`
    );
  }, [groupId, tabs]);

  const panelIds = useMemo(() => {
    return Array.from(tabs.keys()).map(
      (tabIndex: number): string => `${groupId}-panel-${tabIndex}`
    );
  }, [groupId, tabs]);

  const handleSelectTab = (index: number): void => {
    const isOutOfRange = index < 0 || index >= tabs.length;

    if (isOutOfRange) {
      return;
    }

    setActiveIndex(index);
  };

  const renderPillTabs = () => {
    const hasTabs = tabs.length > 0;

    if (!hasTabs) {
      return null;
    }

    return (
      <div className="flex w-full flex-col items-center">
        <TabsList
          tabs={tabs}
          ariaLabel={ariaLabel}
          activeIndex={activeIndex}
          onSelect={handleSelectTab}
          tabIds={tabIds}
          panelIds={panelIds}
        />
        <TabsPanels
          tabs={tabs}
          activeIndex={activeIndex}
          tabIds={tabIds}
          panelIds={panelIds}
        />
      </div>
    );
  };

  return renderPillTabs();
};

export default PillTabs;
