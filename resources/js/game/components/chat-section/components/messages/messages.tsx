import clsx from 'clsx';
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
  const inputRef = useRef<HTMLInputElement>(null);

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

  const handleInputKeyDown = useCallback(
    (event: React.KeyboardEvent<HTMLInputElement>) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        handleSend();
      }
    },
    [handleSend]
  );

  const handleStartPrivateMessage = useCallback((character: string) => {
    const trimmed = character.trim();
    if (!trimmed) {
      return;
    }

    const value = `/m ${trimmed}: `;
    setText(value);

    requestAnimationFrame(() => {
      const element = inputRef.current;

      if (!element) {
        return;
      }

      element.focus();
      element.setSelectionRange(value.length, value.length);
    });
  }, []);

  const handleOnKeyDown = useCallback(
    (character: string) => (event: React.KeyboardEvent<HTMLSpanElement>) => {
      const key = event.key;

      if (key !== 'Enter') {
        return;
      }

      event.preventDefault();
      handleStartPrivateMessage(character);
    },
    [handleStartPrivateMessage]
  );

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
            ref={inputRef}
            type="text"
            placeholder={
              is_silenced && can_talk_again_at
                ? `Silenced until ${can_talk_again_at}`
                : 'Type your message'
            }
            value={text}
            onChange={(event) => setText(event.target.value)}
            onKeyDown={handleInputKeyDown}
            className="flex-grow border border-gray-300 rounded-md p-2"
            disabled={!!is_silenced}
            enterKeyHint="send"
          />
        </div>
        <div
          ref={listRef}
          className="bg-gray-700 dark:bg-gray-800 p-2 w-full h-96 overflow-y-auto rounded-md"
        >
          <ul className="space-y-2">
            {chat.map((row, idx) => {
              console.log('message row', row);

              const displayName: string =
                row.character_name.trim() || (row.name_tag ?? '').trim();
              const name = ` ${row.character_name || ''} ${row.name_tag}`;
              const coords = row.hide_location
                ? '***/***'
                : `${row.x}/${row.y}`;
              const tag = row.map_name ? `[${row.map_name} (${coords})]` : '';

              const customClass = row.custom_class || '';
              const hasCustomClass = customClass.length > 0;

              return (
                <li key={idx}>
                  <span
                    className={clsx(
                      'underline font-bold cursor-pointer',
                      customClass
                    )}
                    onClick={() => handleStartPrivateMessage(displayName)}
                    onKeyDown={handleOnKeyDown(displayName)}
                    role="button"
                    tabIndex={0}
                  >
                    {tag} {name} {': '}
                  </span>

                  <span
                    style={{
                      color: hasCustomClass
                        ? undefined
                        : row.color || undefined,
                    }}
                    className={clsx(hasCustomClass && customClass, 'pl-2')}
                  >
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
