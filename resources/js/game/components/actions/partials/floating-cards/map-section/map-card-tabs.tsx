import React, { ReactNode } from 'react';

import GemProgressTab from './gem-progress-tab';
import MapTabContent from './map-tab-content';
import MapCardTabsProps from './types/map-card-tabs-props';

import PillTabs from 'ui/tabs/pill-tabs';

const MapCardTabs = ({
  character_id: characterId,
  map_tab_content_props: mapTabContentProps,
}: MapCardTabsProps): ReactNode => {
  const tabs = [
    {
      label: 'Map',
      component: MapTabContent,
      props: mapTabContentProps,
    },
    {
      label: 'Gem Progress',
      component: GemProgressTab,
      props: { character_id: characterId },
    },
  ] as const;

  return <PillTabs tabs={tabs} ariaLabel="Map" />;
};

export default MapCardTabs;
