import React from 'react';

import MessagesProps from './components/messages/definitions/message-props';
import Messages from './components/messages/messages';

const Chat = (props: MessagesProps) => {
  return <Messages {...props} />;
};

export default Chat;
