import React, { ReactNode } from 'react';

import ActivityStatusIndicatorProps from './types/activity-status-indicator-props';

const ActivityStatusIndicator = ({
  active,
}: ActivityStatusIndicatorProps): ReactNode => {
  if (!active) {
    return null;
  }

  return (
    <span className="inline-flex items-center">
      <i
        className="far fa-bell text-mango-tango-600 dark:text-mango-tango-400 animate-pulse motion-reduce:animate-none"
        aria-hidden="true"
      />
      <span className="sr-only">New announcements available</span>
    </span>
  );
};

export default ActivityStatusIndicator;
