import clsx from 'clsx';
import React from 'react';

import { TabTupleFromProps } from 'ui/tabs/types/tab-item';
import TabsListProps from 'ui/tabs/types/tab-list-props';

const TabsList = <PTuple extends readonly object[]>({
  tabs,
  ariaLabel,
  activeIndex,
  onSelect,
  tabIds,
  panelIds,
  additional_tab_css,
}: TabsListProps<PTuple>) => {
  const tabItemAt = (index: number): TabTupleFromProps<PTuple>[number] => {
    return tabs[index] as TabTupleFromProps<PTuple>[number];
  };

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

  const renderIconLeft = (tabIndex: number) => {
    const tabItem = tabItemAt(tabIndex);

    if (!tabItem.activity_icon) {
      return (
        <span
          className="block h-[1em] invisible max-sm:w-[1rem] sm:w-[1.25rem]"
          aria-hidden="true"
        />
      );
    }

    return (
      <i
        className={clsx(
          'leading-none text-center max-sm:w-[1rem] sm:w-[1.25rem]',
          tabItem.icon_styles,
          tabItem.activity_icon
        )}
        aria-hidden="true"
      />
    );
  };

  const renderIconRightSpacer = () => {
    return (
      <span
        className="block h-[1em] invisible max-sm:w-[1rem] sm:w-[1.25rem]"
        aria-hidden="true"
      />
    );
  };

  const renderSrOnlyNew = (tabIndex: number) => {
    const tabItem = tabItemAt(tabIndex);

    if (!tabItem.activity_icon) {
      return null;
    }

    return <span className="sr-only">(new)</span>;
  };

  const renderLabel = (tabIndex: number) => {
    const tabItem = tabItemAt(tabIndex);

    return (
      <span className="inline-grid items-center justify-center min-w-0 max-sm:grid-cols-[1rem_1fr_1rem] sm:grid-cols-[1.25rem_1fr_1.25rem] max-sm:gap-1 sm:gap-1.5">
        {renderIconLeft(tabIndex)}
        <span
          className="min-w-0 text-center leading-tight max-sm:whitespace-normal max-sm:break-normal sm:whitespace-nowrap max-sm:text-xs sm:text-sm"
          style={{ overflowWrap: 'normal', wordBreak: 'normal' }}
        >
          {tabItem.label}
        </span>
        {renderIconRightSpacer()}
        {renderSrOnlyNew(tabIndex)}
      </span>
    );
  };

  const renderTabsList = () => {
    const hasTabs = tabs.length > 0;

    if (!hasTabs) {
      return null;
    }

    const totalTabs = tabs.length;
    const stepPercent = 100 / totalTabs;
    const indicatorWidth = `calc(${stepPercent}%)`;
    const indicatorTransform = `translateX(${activeIndex * 100}%)`;

    return (
      <div
        role="tablist"
        aria-label={ariaLabel}
        aria-orientation="horizontal"
        onKeyDown={handleTabListKeyDown}
        className={clsx(
          'relative flex rounded-md border border-gray-300 bg-gray-100 p-1 dark:border-gray-600 dark:bg-gray-700',
          additional_tab_css
        )}
      >
        <div
          aria-hidden="true"
          className="pointer-events-none absolute left-1 top-1 bottom-1 rounded-md border border-danube-500 bg-gray-300 shadow-sm transition-transform duration-300 ease-out dark:border-danube-300 dark:bg-gray-500"
          style={{ width: indicatorWidth, transform: indicatorTransform }}
        />
        {tabs.map((_, tabIndex) => {
          const isSelected = tabIndex === activeIndex;

          const className = clsx(
            'relative z-[1] flex-1 rounded-md text-center font-medium focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 dark:focus-visible:ring-brand-400 max-sm:px-2 max-sm:py-1 sm:px-3 sm:py-1.5 max-sm:text-xs sm:text-sm',
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
              {renderLabel(tabIndex)}
            </button>
          );
        })}
      </div>
    );
  };

  return renderTabsList();
};

export default TabsList;
