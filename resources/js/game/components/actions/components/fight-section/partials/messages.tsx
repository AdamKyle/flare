import React, { ReactNode } from 'react';

import AttackMessageDefinition from '../deffinitions/attack-message-definition';
import { fetchAttackMessageColorForType } from '../helpers/fetch-attack-message-color-for-type';
import AttackMessagesProps from '../types/attack-messages-props';

const Messages = (props: AttackMessagesProps): ReactNode => {
  return props.messages.map((message: AttackMessageDefinition) => {
    const messageColor = fetchAttackMessageColorForType(message.type);

    return <div className={messageColor}>{message.message}</div>;
  });
};

export default Messages;
