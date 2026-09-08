import React, { ReactNode } from 'react';

import ActiveBoonCard from './active-boon-card';
import ActiveBoonsContentProps from './types/active-boons-content-props';
import { useProgressiveActiveBoons } from '../hooks/use-progressive-active-boons';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const ActiveBoonsContent = (props: ActiveBoonsContentProps): ReactNode => {
  const resetKey = props.boons.map((boon) => boon.id).join('-');

  const { visible_count: visibleCount, handle_scroll: handleScroll } =
    useProgressiveActiveBoons({
      total_items: props.boons.length,
      reset_key: resetKey,
    });

  const visibleBoons = props.boons.slice(0, visibleCount);
  const hasBoons = props.boons.length > 0;

  const renderAlerts = (): ReactNode => {
    if (!props.error && !props.mutation_error && !props.success_message) {
      return null;
    }

    if (props.error || props.mutation_error) {
      return (
        <Alert variant={AlertVariant.DANGER}>
          {props.error ?? props.mutation_error}
        </Alert>
      );
    }

    return (
      <Alert variant={AlertVariant.SUCCESS}>{props.success_message}</Alert>
    );
  };

  const renderEmptyState = (): ReactNode => {
    if (hasBoons) {
      return null;
    }

    return (
      <div className="space-y-2 text-sm text-gray-600 dark:text-gray-400">
        <p>No Active Boons.</p>
        <a
          href="/information/alchemy"
          target="_blank"
          rel="noopener noreferrer"
          className="focus:ring-danube-500 dark:focus:ring-danube-300 text-danube-700 dark:text-danube-300 rounded-sm underline focus:ring-2 focus:outline-none"
        >
          What are boons and how do I get them?
        </a>
      </div>
    );
  };

  const renderBoonsList = (): ReactNode => {
    if (!hasBoons) {
      return null;
    }

    return (
      <InfiniteScroll
        height_class={props.bounded_height ? 'h-full min-h-0' : 'max-h-[32rem]'}
        handle_scroll={handleScroll}
      >
        <div className="flex flex-col gap-4">
          {visibleBoons.map((boon) => (
            <ActiveBoonCard
              key={boon.id}
              boon={boon}
              filling={props.filling_boon_id === boon.id}
              removing={props.removing_boon_id === boon.id}
              on_view_source_item={() => props.on_view_source_item(boon)}
              on_fill_up={() => props.on_fill_up(boon.id)}
              on_remove={() => props.on_remove(boon.id)}
            />
          ))}
        </div>
      </InfiniteScroll>
    );
  };

  if (props.loading) {
    return <InfiniteLoader />;
  }

  return (
    <div
      className={
        props.bounded_height
          ? 'flex h-full min-h-0 flex-col gap-4'
          : 'flex flex-col gap-4'
      }
    >
      {renderAlerts()}
      {renderEmptyState()}
      {renderBoonsList()}
    </div>
  );
};

export default ActiveBoonsContent;
