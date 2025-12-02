import React from 'react';

import AnnouncementCardProps from './types/annacounement-card-props';

const AnnouncementCard = ({
  announcement,
  on_click_announcement,
}: AnnouncementCardProps) => {
  const handleCardClick = () => {
    if (!on_click_announcement) {
      return;
    }

    on_click_announcement(announcement.id);
  };

  const handleKeyDown = (event: React.KeyboardEvent<HTMLDivElement>) => {
    if (!on_click_announcement) {
      return;
    }

    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      on_click_announcement(announcement.id);
    }
  };

  return (
    <div
      role="button"
      tabIndex={0}
      aria-label={`View announcement: ${announcement.event_name}`}
      onClick={handleCardClick}
      onKeyDown={handleKeyDown}
      className="group border-glacier-200 bg-glacier-50 focus-visible:ring-glacier-500 dark:border-glacier-700 dark:bg-glacier-900 dark:focus-visible:ring-glacier-300 my-2 flex w-full flex-col items-center gap-4 rounded-xl border p-6 text-left transition-shadow duration-150 hover:shadow-lg focus:shadow-lg focus:outline-none focus-visible:ring-2"
    >
      <p className="text-glacier-700 dark:text-glacier-200 text-center text-xl font-extrabold">
        {announcement.event_name}
      </p>

      <p className="text-glacier-600 dark:text-glacier-300 text-center text-sm">
        <span className="text-glacier-700 dark:text-glacier-100 font-bold">
          Ends:
        </span>{' '}
        {announcement.expires_at_formatted}
      </p>

      <div className="prose text-glacier-700 dark:prose-invert dark:text-glacier-200 max-w-full text-center text-sm leading-normal">
        <p>{announcement.message}</p>
      </div>
    </div>
  );
};

export default AnnouncementCard;
