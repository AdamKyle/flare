import React, { ReactNode } from 'react';

import ActiveGemScrollCard from './active-gem-scroll-card';
import ActiveGemScrollsSectionProps from './types/active-gem-scrolls-section-props';
import { useGemScrollActions } from '../api/hooks/use-gem-scroll-actions';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';

const ActiveGemScrollsSection = ({
  character_id: characterId,
  scrolls,
  on_changed: onChanged,
}: ActiveGemScrollsSectionProps): ReactNode => {
  const {
    actingScrollId,
    successMessage,
    mutationError,
    fillMapScroll,
    fillLocationScroll,
    removeMapScroll,
    removeLocationScroll,
  } = useGemScrollActions({ characterId, onSuccess: onChanged });

  const renderAlerts = (): ReactNode => {
    if (!mutationError && !successMessage) {
      return null;
    }

    return (
      <Alert
        variant={mutationError ? AlertVariant.DANGER : AlertVariant.SUCCESS}
      >
        {mutationError ?? successMessage}
      </Alert>
    );
  };

  if (scrolls.length === 0) {
    return (
      <div className="text-sm text-gray-600 dark:text-gray-400">
        No active Gem Scrolls for this Gem World.
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-3">
      {renderAlerts()}
      {scrolls.map((scroll) => (
        <ActiveGemScrollCard
          key={`${scroll.is_map_scroll ? 'map' : 'location'}-${scroll.id}`}
          scroll={scroll}
          character_id={characterId}
          acting={actingScrollId === scroll.id}
          on_remove={() =>
            void (scroll.is_map_scroll
              ? removeMapScroll(scroll.id)
              : removeLocationScroll(scroll.id))
          }
          on_fill={(alchemyBagSlotId) =>
            void (scroll.is_map_scroll
              ? fillMapScroll(scroll.id, alchemyBagSlotId)
              : fillLocationScroll(scroll.id, alchemyBagSlotId))
          }
        />
      ))}
    </div>
  );
};

export default ActiveGemScrollsSection;
