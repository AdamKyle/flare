import React, { ReactNode } from 'react';

import Messages from './partials/messages';
import AttackMessagesProps from './types/attack-messages-props';

const AttackMessages = (props: AttackMessagesProps): ReactNode => {
  return (
    <div
      className="
                mx-auto mt-4 flex items-center justify-center
                w-full gap-x-3 text-lg leading-none
            "
    >
      <div className="w-full text-center italic space-y-2">
        <Messages messages={props.messages} />
      </div>
    </div>
  );
};

export default AttackMessages;
