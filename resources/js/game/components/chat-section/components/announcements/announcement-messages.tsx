import React from 'react';

import AnnouncementMessagesProps from './definitions/announcement-messages-props';

import Card from 'ui/cards/card';

const AnnouncementMessages = ({ announcements }: AnnouncementMessagesProps) => {
  return (
    <div className="w-full lg:w-3/4 mx-auto my-4">
      <Card>
        <div className="bg-gray-700 dark:bg-gray-800 w-full h-96 overflow-y-auto rounded-md text-purple-200 dark:text-purple-400 p-4">
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
