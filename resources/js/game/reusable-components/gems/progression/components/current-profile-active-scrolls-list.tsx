import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useEffect, useRef } from 'react';

import ActiveGemScrollCard from './active-gem-scroll-card';
import CurrentProfileActiveScrollsListProps from './types/current-profile-active-scrolls-list-props';
import { useCurrentProfileActiveGemScrolls } from '../api/hooks/use-current-profile-active-gem-scrolls';
import { useGemScrollActions } from '../api/hooks/use-gem-scroll-actions';

import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const CurrentProfileActiveScrollsList = ({
  character_id: characterId,
  refresh_token: refreshToken,
}: CurrentProfileActiveScrollsListProps): ReactNode => {
  const {
    data,
    loading,
    error,
    isLoadingMore,
    canLoadMore,
    onEndReached,
    setRefresh,
  } = useCurrentProfileActiveGemScrolls(characterId);

  const isFirstRenderRef = useRef(true);

  useEffect(() => {
    if (isFirstRenderRef.current) {
      isFirstRenderRef.current = false;

      return;
    }

    setRefresh((previous) => !previous);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [refreshToken]);

  const {
    actingScrollId,
    successMessage,
    mutationError,
    fillMapScroll,
    fillLocationScroll,
    removeMapScroll,
    removeLocationScroll,
  } = useGemScrollActions({
    characterId,
    onSuccess: () => setRefresh((previous) => !previous),
  });

  const handleScroll = (event: React.UIEvent<HTMLDivElement>): void => {
    const target = event.currentTarget;
    const nearBottom =
      target.scrollHeight - target.scrollTop - target.clientHeight < 100;

    if (nearBottom) {
      onEndReached();
    }
  };

  const renderAlerts = (): ReactNode => {
    if (!mutationError && !successMessage) {
      return null;
    }

    return <ApiErrorAlert apiError={mutationError ?? successMessage ?? ''} />;
  };

  if (loading && data.length === 0) {
    return <InfiniteLoader />;
  }

  if (error) {
    return <ApiErrorAlert apiError={error.message ?? ''} />;
  }

  if (data.length === 0) {
    return (
      <p className="text-sm text-gray-600 dark:text-gray-400">
        No active Gem Scrolls for this Gem World.
      </p>
    );
  }

  return (
    <div className="flex flex-col gap-2">
      {renderAlerts()}
      <InfiniteScroll
        handle_scroll={handleScroll}
        height_class="h-auto max-h-[400px]"
      >
        <div className="flex flex-col gap-2">
          {data.map((scroll) => (
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
          {isLoadingMore && <InfiniteLoader />}
          {!canLoadMore && data.length > 0 && (
            <p className="text-center text-xs text-gray-500 dark:text-gray-400">
              No more active Gem Scrolls.
            </p>
          )}
        </div>
      </InfiniteScroll>
    </div>
  );
};

export default CurrentProfileActiveScrollsList;
