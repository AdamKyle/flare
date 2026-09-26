import React, { createElement, Fragment } from 'react';

import TabsPanelsProps from 'ui/tabs/types/tab-panel-props';

const TabsPanels = <PTuple extends readonly object[]>({
  tabs,
  activeIndex,
  tabIds,
  panelIds,
  keep_panels_mounted = false,
}: TabsPanelsProps<PTuple>) => {
  const renderPanelContent = (tabIndex: number): React.ReactNode => {
    const tabItem = tabs[tabIndex];

    return (
      <Fragment key={panelIds[tabIndex]}>
        {createElement(tabItem.component, tabItem.props)}
      </Fragment>
    );
  };

  const renderActivePanel = () => {
    return (
      <div className="w-full">
        <div
          id={panelIds[activeIndex]}
          role="tabpanel"
          aria-labelledby={tabIds[activeIndex]}
          className="mt-4 outline-none"
          tabIndex={0}
        >
          {renderPanelContent(activeIndex)}
        </div>
      </div>
    );
  };

  const renderMountedPanel = (tabIndex: number) => {
    const isActive = tabIndex === activeIndex;

    return (
      <div
        key={panelIds[tabIndex]}
        id={panelIds[tabIndex]}
        role="tabpanel"
        aria-labelledby={tabIds[tabIndex]}
        className="mt-4 outline-none"
        tabIndex={isActive ? 0 : -1}
        hidden={!isActive}
      >
        {renderPanelContent(tabIndex)}
      </div>
    );
  };

  const renderMountedPanels = () => {
    return (
      <div className="w-full">
        {Array.from(tabs.keys()).map(renderMountedPanel)}
      </div>
    );
  };

  const renderTabPanels = () => {
    const hasTabs = tabs.length > 0;

    if (!hasTabs) {
      return null;
    }

    if (keep_panels_mounted) {
      return renderMountedPanels();
    }

    return renderActivePanel();
  };

  return renderTabPanels();
};

export default TabsPanels;
