import React, { ReactNode, useState } from 'react';

import MonsterGemEffectContextCard from './monster-gem-effect-context-card';
import {
  locationCardBaseStyles,
  locationCardFocusRingStyles,
  locationCardIconStyles,
  locationCardPrimaryTextStyles,
  locationCardSecondaryTextStyles,
  locationCardThemeStyles,
} from '../../location/styles/location-card-styles';
import MonsterSpecialLocationEffectsTabPanelProps from '../types/monster-special-location-effects-tab-panel-props';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';

const PAGE_SIZE = 10;
const SCROLL_LOAD_THRESHOLD_PX = 96;

const MonsterSpecialLocationEffectsTabPanel = ({
  contexts,
  navigation,
}: MonsterSpecialLocationEffectsTabPanelProps): ReactNode => {
  const [selectedKey, setSelectedKey] = useState<string | null>(null);
  const [visibleCount, setVisibleCount] = useState(PAGE_SIZE);

  if (contexts.length <= 1) {
    return (
      <div>
        {contexts.map((context) => (
          <MonsterGemEffectContextCard
            key={context.key}
            context={context}
            navigation={navigation}
          />
        ))}
      </div>
    );
  }

  const selectedContext = contexts.find(
    (context) => context.key === selectedKey
  );

  const handleScroll = (event: React.UIEvent<HTMLDivElement>): void => {
    const target = event.currentTarget;
    const distanceFromBottom =
      target.scrollHeight - target.scrollTop - target.clientHeight;

    if (distanceFromBottom > SCROLL_LOAD_THRESHOLD_PX) {
      return;
    }

    setVisibleCount((previous) =>
      Math.min(contexts.length, previous + PAGE_SIZE)
    );
  };

  const visibleContexts = contexts.slice(0, visibleCount);

  return (
    <div className="relative">
      <InfiniteScroll
        handle_scroll={handleScroll}
        height_class="max-h-[32rem]"
        additional_css="flex flex-col gap-3"
      >
        {visibleContexts.map((context) => (
          <button
            key={context.key}
            type="button"
            onClick={() => setSelectedKey(context.key)}
            aria-label={`Open transformed stats for ${context.label}`}
            className={`${locationCardBaseStyles()} ${locationCardThemeStyles()} ${locationCardFocusRingStyles()}`}
          >
            <i
              className={`fas fa-map-marker-alt text-2xl ${locationCardIconStyles()}`}
              aria-hidden="true"
            />
            <div className="flex min-w-0 flex-1 flex-col gap-1">
              <span
                className={`text-sm font-semibold break-words ${locationCardPrimaryTextStyles()}`}
              >
                {context.label}
              </span>
              {(context.game_map || context.location) && (
                <span
                  className={`text-xs ${locationCardSecondaryTextStyles()}`}
                >
                  {context.location?.name ?? context.game_map?.name}
                </span>
              )}
            </div>
          </button>
        ))}
      </InfiniteScroll>

      {selectedContext && (
        <StackedCard
          on_close={() => setSelectedKey(null)}
          aria_label={selectedContext.label}
          content_mode={StackedCardContentMode.FULL_BLEED}
        >
          <div className="h-full min-h-0 overflow-y-auto p-4">
            <MonsterGemEffectContextCard
              context={selectedContext}
              navigation={navigation}
            />
          </div>
        </StackedCard>
      )}
    </div>
  );
};

export default MonsterSpecialLocationEffectsTabPanel;
