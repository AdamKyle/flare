import clsx from 'clsx';
import React from 'react';

import TabsListProps from 'ui/tabs/types/tab-list-props';

const TabsList = <PTuple extends readonly object[]>({
  tabs,
  ariaLabel,
  activeIndex,
  onSelect,
  tabIds,
  panelIds,
}: TabsListProps<PTuple>) => {
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

  const handleClickTab = (index: number): void => {
    onSelect(index);
  };

  const renderTabsList = () => {
    const hasTabs = tabs.length > 0;

    if (!hasTabs) {
      return null;
    }

    const totalTabs = tabs.length;
    const stepPercent = 100 / totalTabs;
    const indicatorWidth = `calc(${stepPercent}% - 0.5rem)`;
    const indicatorTransform = `translateX(${activeIndex * 100}%)`;

    return (
      <div
        role="tablist"
        aria-label={ariaLabel}
        aria-orientation="horizontal"
        onKeyDown={handleTabListKeyDown}
        className="relative flex w-full max-w-md rounded-md border border-gray-300 bg-gray-100 p-1 dark:border-gray-600 dark:bg-gray-700"
      >
        <div
          aria-hidden="true"
          className="pointer-events-none absolute left-1 top-1 bottom-1 rounded-md border border-danube-500 bg-gray-300 shadow-sm transition-transform duration-300 ease-out dark:border-danube-300 dark:bg-gray-500"
          style={{ width: indicatorWidth, transform: indicatorTransform }}
        />
        {tabs.map((tabItem, tabIndex) => {
          const isSelected = tabIndex === activeIndex;

          const className = clsx(
            'relative z-[1] flex-1 rounded-md px-3 py-1.5 text-center text-sm font-medium focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 dark:focus-visible:ring-brand-400',
            isSelected
              ? 'text-danube-700 dark:text-danube-200'
              : 'text-gray-800 dark:text-gray-200'
          );

          return (
            <button
              key={tabIds[tabIndex]}
              id={tabIds[tabIndex]}
              role="tab"
              aria-selected={isSelected}
              aria-controls={panelIds[tabIndex]}
              tabIndex={isSelected ? 0 : -1}
              className={className}
              onClick={() => {
                handleClickTab(tabIndex);
              }}
              type="button"
            >
              {tabItem.label}
            </button>
          );
        })}
      </div>
    );
  };

  return renderTabsList();
};

export default TabsList;
