import clsx from 'clsx';
import React, { useId, useMemo, useState } from 'react';

import { PillTabsAlignment } from 'ui/tabs/enums/pill-tabs-alignment';
import TabsList from 'ui/tabs/tabs-list';
import TabsPanels from 'ui/tabs/tabs-panels';
import PillTabsProps from 'ui/tabs/types/pill-tabs-props';

const PillTabs = <PTuple extends readonly object[]>({
  tabs,
  ariaLabel = 'Tabs',
  initialIndex = 0,
  activeIndex,
  additional_tab_css,
  onActiveIndexChange,
  alignment = PillTabsAlignment.CENTER,
}: PillTabsProps<PTuple>) => {
  const [internalActiveIndex, setInternalActiveIndex] =
    useState<number>(initialIndex);

  const isControlled = activeIndex !== undefined;
  const resolvedActiveIndex =
    activeIndex !== undefined ? activeIndex : internalActiveIndex;

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

    if (!isControlled) {
      setInternalActiveIndex(index);
    }

    if (onActiveIndexChange) {
      onActiveIndexChange(index);
    }
  };

  const renderPillTabs = () => {
    const hasTabs = tabs.length > 0;

    if (!hasTabs) {
      return null;
    }

    return (
      <div
        className={clsx(
          'flex w-full flex-col',
          alignment === PillTabsAlignment.START ? 'items-start' : 'items-center'
        )}
      >
        <TabsList
          tabs={tabs}
          ariaLabel={ariaLabel}
          activeIndex={resolvedActiveIndex}
          onSelect={handleSelectTab}
          tabIds={tabIds}
          panelIds={panelIds}
          additional_tab_css={additional_tab_css}
        />
        <TabsPanels
          tabs={tabs}
          activeIndex={resolvedActiveIndex}
          tabIds={tabIds}
          panelIds={panelIds}
        />
      </div>
    );
  };

  return renderPillTabs();
};

export default PillTabs;
