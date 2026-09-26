import clsx from 'clsx';
import React, { ReactNode } from 'react';

import { TabItemPresentation } from 'ui/tabs/types/tab-item';
import TabsListProps from 'ui/tabs/types/tab-list-props';

const TabsList = ({
  tabs,
  ariaLabel,
  activeIndex,
  onSelect,
  tabIds,
  panelIds,
  additional_tab_css,
}: TabsListProps) => {
  const handleTabListKeyDown = (
    event: React.KeyboardEvent<HTMLDivElement>
  ): void => {
    if (event.key === 'ArrowRight') {
      event.preventDefault();
      onSelect((activeIndex + 1) % tabs.length);
    }

    if (event.key === 'ArrowLeft') {
      event.preventDefault();
      onSelect((activeIndex - 1 + tabs.length) % tabs.length);
    }

    if (event.key === 'Home') {
      event.preventDefault();
      onSelect(0);
    }

    if (event.key === 'End') {
      event.preventDefault();
      onSelect(tabs.length - 1);
    }
  };

  const renderIconSpacer = (): ReactNode => (
    <span className="block w-4 shrink-0 sm:w-5" aria-hidden="true" />
  );

  const renderActivityIcon = (tabItem: TabItemPresentation): ReactNode => {
    if (!tabItem.activity_icon) {
      return renderIconSpacer();
    }

    return (
      <i
        className={clsx(
          'w-4 shrink-0 text-center leading-none sm:w-5',
          tabItem.icon_styles,
          tabItem.activity_icon
        )}
        aria-hidden="true"
      />
    );
  };

  const renderActivityScreenReaderText = (
    tabItem: TabItemPresentation
  ): ReactNode => {
    if (!tabItem.activity_icon) {
      return null;
    }

    return <span className="sr-only">(new)</span>;
  };

  const renderLabel = (tabItem: TabItemPresentation): ReactNode => (
    <span className="flex min-w-0 items-center justify-center gap-1 sm:gap-1.5">
      {renderActivityIcon(tabItem)}
      <span className="min-w-0 flex-1 text-center leading-tight whitespace-normal">
        {tabItem.label}
      </span>
      {renderIconSpacer()}
      {renderActivityScreenReaderText(tabItem)}
    </span>
  );

  const renderTab = (tabItem: TabItemPresentation, tabIndex: number) => {
    const isSelected = tabIndex === activeIndex;

    return (
      <button
        key={tabIds[tabIndex]}
        id={tabIds[tabIndex]}
        role="tab"
        aria-selected={isSelected}
        aria-controls={panelIds[tabIndex]}
        tabIndex={isSelected ? 0 : -1}
        className={clsx(
          'focus-visible:ring-brand-600 dark:focus-visible:ring-brand-400 flex-1 rounded-md border border-transparent px-2 py-1 text-center text-xs font-medium transition-colors focus:outline-none focus-visible:ring-2 sm:px-3 sm:py-1.5 sm:text-sm',
          isSelected
            ? 'border-danube-500 text-danube-700 dark:border-danube-300 dark:text-danube-200 bg-gray-300 shadow-sm dark:bg-gray-500'
            : 'text-gray-800 dark:text-gray-200'
        )}
        onClick={() => onSelect(tabIndex)}
        type="button"
      >
        {renderLabel(tabItem)}
      </button>
    );
  };

  if (tabs.length === 0) {
    return null;
  }

  return (
    <div
      role="tablist"
      aria-label={ariaLabel}
      aria-orientation="horizontal"
      onKeyDown={handleTabListKeyDown}
      className={clsx(
        'flex rounded-md border border-gray-300 bg-gray-100 p-1 dark:border-gray-600 dark:bg-gray-700',
        additional_tab_css
      )}
    >
      {tabs.map(renderTab)}
    </div>
  );
};

export default TabsList;
