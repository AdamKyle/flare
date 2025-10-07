import React from 'react';

import TabsPanelsProps from 'ui/tabs/types/tab-panel-props';

const TabsPanels = ({
  tabs,
  activeIndex,
  tabIds,
  panelIds,
}: TabsPanelsProps) => {
  const isObjectRecord = (value: unknown): value is Record<string, unknown> => {
    if (typeof value === 'object' && value !== null) {
      return true;
    }

    return false;
  };

  const renderTabPanels = () => {
    const hasTabs = tabs.length > 0;

    if (!hasTabs) {
      return null;
    }

    const activeItem = tabs[activeIndex];
    const ActiveComponent = activeItem.component;
    const maybeProps = (activeItem as { props?: unknown }).props;

    let content;

    if (isObjectRecord(maybeProps)) {
      content = <ActiveComponent {...maybeProps} />;
    } else {
      content = <ActiveComponent />;
    }

    return (
      <div className="w-full max-w-md">
        <div
          id={panelIds[activeIndex]}
          role="tabpanel"
          aria-labelledby={tabIds[activeIndex]}
          className="mt-4 outline-none"
          tabIndex={0}
        >
          {content}
        </div>
      </div>
    );
  };

  return renderTabPanels();
};

export default TabsPanels;
