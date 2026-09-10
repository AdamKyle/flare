import React, { ReactNode } from 'react';

import {
  locationCardBaseStyles,
  locationCardFocusRingStyles,
  locationCardIconStyles,
  locationCardPrimaryTextStyles,
  locationCardSecondaryTextStyles,
  locationCardThemeStyles,
} from '../../location/styles/location-card-styles';
import MonsterSpecialLocationEffectsTabPanelProps from '../types/monster-special-location-effects-tab-panel-props';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const MonsterSpecialLocationEffectsTabPanel = ({
  contexts,
  loading,
  loading_more: loadingMore,
  error,
  has_more: hasMore,
  on_load_next: onLoadNext,
  on_open_context: onOpenContext,
}: MonsterSpecialLocationEffectsTabPanelProps): ReactNode => {
  const handleScroll = (event: React.UIEvent<HTMLDivElement>): void => {
    const { scrollTop, scrollHeight, clientHeight } = event.currentTarget;

    if (!hasMore || loadingMore) {
      return;
    }

    if (scrollTop + clientHeight < scrollHeight - 10) {
      return;
    }

    onLoadNext();
  };

  if (loading && contexts.length === 0) {
    return <InfiniteLoader />;
  }

  if (error) {
    return <Alert variant={AlertVariant.DANGER}>{error}</Alert>;
  }

  return (
    <InfiniteScroll height_class="max-h-[32rem]" handle_scroll={handleScroll}>
      <div className="flex flex-col gap-2">
        {contexts.map((context) => (
          <button
            key={context.key}
            type="button"
            onClick={() => onOpenContext?.(context)}
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
        {loadingMore && <InfiniteLoader />}
      </div>
    </InfiniteScroll>
  );
};

export default MonsterSpecialLocationEffectsTabPanel;
