import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import GemProgressHistoryRowCard from './gem-progress-history-row-card';
import GemProgressHistoryListProps from './types/gem-progress-history-list-props';
import { useAllGemProfileParticipation } from '../api/hooks/use-all-gem-profile-participation';

import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const GemProgressHistoryList = ({
  character_id: characterId,
}: GemProgressHistoryListProps): ReactNode => {
  const { data, loading, error, isLoadingMore, canLoadMore, onEndReached } =
    useAllGemProfileParticipation(characterId);

  const handleScroll = (event: React.UIEvent<HTMLDivElement>): void => {
    const target = event.currentTarget;
    const nearBottom =
      target.scrollHeight - target.scrollTop - target.clientHeight < 100;

    if (nearBottom) {
      onEndReached();
    }
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
        No Gem World progression yet.
      </p>
    );
  }

  return (
    <div className="flex h-full min-h-0 flex-col gap-2 px-4 py-4 sm:px-5">
      <InfiniteScroll
        handle_scroll={handleScroll}
        height_class="h-full max-h-full"
      >
        <div className="flex flex-col gap-2">
          {data.map((row) => (
            <GemProgressHistoryRowCard
              key={`${row.is_map_profile ? 'map' : 'location'}-${row.profile_id}`}
              row={row}
            />
          ))}
          {isLoadingMore && <InfiniteLoader />}
          {!canLoadMore && data.length > 0 && (
            <p className="text-center text-xs text-gray-500 dark:text-gray-400">
              No more Gem World progression.
            </p>
          )}
        </div>
      </InfiniteScroll>
    </div>
  );
};

export default GemProgressHistoryList;
