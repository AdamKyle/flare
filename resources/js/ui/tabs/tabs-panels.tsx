import React from 'react';

import TabsPanelsProps from 'ui/tabs/types/tab-panel-props';

type PropsOf<C> = C extends (props: infer P) => React.ReactNode
  ? P
  : Record<never, never>;

const TabsPanels = <PTuple extends readonly object[]>({
  tabs,
  activeIndex,
  tabIds,
  panelIds,
}: TabsPanelsProps<PTuple>) => {
  const hasProps = <P extends object>(item: {
    props?: P;
  }): item is { props: P } => {
    const exists = Object.prototype.hasOwnProperty.call(item, 'props');

    if (!exists) {
      return false;
    }

    return item.props !== undefined;
  };

  const renderTabPanels = () => {
    const hasTabs = tabs.length > 0;

    if (!hasTabs) {
      return null;
    }

    const activeItem = tabs[activeIndex];

    const ActiveComponent = activeItem.component;

    type ActiveProps = PropsOf<typeof ActiveComponent>;

    const maybeWithProps = activeItem as { props?: ActiveProps };

    let content = null;

    if (hasProps<ActiveProps>(maybeWithProps)) {
      const provided = maybeWithProps.props;

      content = <ActiveComponent {...provided} key={panelIds[activeIndex]} />;
    } else {
      const empty = {} as ActiveProps;

      content = <ActiveComponent {...empty} key={panelIds[activeIndex]} />;
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
