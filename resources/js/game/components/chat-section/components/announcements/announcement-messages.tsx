import React from 'react';

import AnnouncementMessagesProps from './definitions/announcement-messages-props';

import Card from 'ui/cards/card';

const AnnouncementMessages = ({ announcements }: AnnouncementMessagesProps) => {
  return (
    <div className="mx-auto my-4 w-full lg:w-3/4">
      <Card>
        <div className="h-96 w-full overflow-y-auto rounded-md bg-gray-700 p-4 text-purple-200 dark:bg-gray-800 dark:text-purple-400">
          <ul className="space-y-2">
            {announcements.map((row) => (
              <li key={row.id}>{row.message}</li>
            ))}
          </ul>
        </div>
      </Card>
    </div>
  );
};

export default AnnouncementMessages;
