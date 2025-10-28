import React, { useCallback, useRef, useState } from 'react';

import MessagesProps from './definitions/message-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Card from 'ui/cards/card';

const Messages = ({
  is_silenced,
  can_talk_again_at,
  chat,
  set_tab_to_updated,
  push_silenced_message,
  push_private_message_sent,
  push_error_message,
  on_send,
}: MessagesProps) => {
  const [text, setText] = useState('');
  const listRef = useRef<HTMLDivElement>(null);

  const canSend = /\S/.test(text);

  const handleSend = useCallback(() => {
    if (is_silenced) {
      push_silenced_message();
      return;
    }

    if (!canSend) {
      push_error_message('Message cannot be empty.');
      return;
    }

    const privateMatch = text.match(/^\/m\s+([^:]+):\s*(.+)$/i);
    if (privateMatch) {
      const [, target, msg] = privateMatch;
      push_private_message_sent(['/m', target, msg]);
    }

    on_send(text);
    set_tab_to_updated('chat');
    setText('');
  }, [
    is_silenced,
    canSend,
    on_send,
    push_error_message,
    push_private_message_sent,
    push_silenced_message,
    set_tab_to_updated,
    text,
  ]);

  return (
    <div className="w-full lg:w-3/4 mx-auto my-4">
      <Card>
        <div className="flex items-center mb-2">
          <Button
            label="Send"
            on_click={handleSend}
            variant={ButtonVariant.PRIMARY}
            additional_css="mr-2"
            disabled={!!is_silenced}
          />
          <input
            type="text"
            placeholder={
              is_silenced && can_talk_again_at
                ? `Silenced until ${can_talk_again_at}`
                : 'Type your message'
            }
            value={text}
            onChange={(e) => setText(e.target.value)}
            className="flex-grow border border-gray-300 rounded-md p-2"
            disabled={!!is_silenced}
          />
        </div>
        <div
          ref={listRef}
          className="bg-gray-700 dark:bg-gray-800 p-2 w-full h-96 overflow-y-auto rounded-md text-gray-400"
        >
          <ul className="space-y-4">
            {chat.map((row, idx) => {
              const name = row.character_name || '';
              const coords = row.hide_location
                ? '***/***'
                : `${row.x}/${row.y}`;
              const tag = row.map_name ? `[${row.map_name} (${coords})]` : '';

              return (
                <li key={idx}>
                  <span className="underline font-bold">
                    {tag} {name}
                  </span>
                  {': '}
                  <span style={{ color: row.color || undefined }}>
                    {row.message}
                  </span>
                </li>
              );
            })}
          </ul>
        </div>
      </Card>
    </div>
  );
};

export default Messages;
