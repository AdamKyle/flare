import clsx from 'clsx';
import React, { useCallback, useRef, useState } from 'react';

import MessagesProps from './definitions/message-props';
import ChatType from '../../../../api-definitions/chat/chat-message-definition';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Card from 'ui/cards/card';

interface ChatRowViewModel {
  sender_name: string;
  has_sender: boolean;
  name_label: string;
  location_tag: string;
  color: string | undefined;
  custom_class: string;
  has_custom_class: boolean;
}

const normalizeChatName = (value: string | null | undefined): string =>
  (value ?? '').trim();

const buildChatRowViewModel = (row: ChatType): ChatRowViewModel => {
  const characterName = normalizeChatName(row.character_name);
  const nameTagLabel = normalizeChatName(row.name_tag);
  const senderName = characterName || nameTagLabel;
  const nameLabel = [characterName, nameTagLabel]
    .filter((part) => part.length > 0)
    .join(' ');
  const coords = row.hide_location ? '***/***' : `${row.x}/${row.y}`;
  const customClass = row.custom_class || '';

  return {
    sender_name: senderName,
    has_sender: senderName.length > 0,
    name_label: nameLabel,
    location_tag: row.map_name ? `[${row.map_name} (${coords})]` : '',
    color: customClass.length > 0 ? undefined : row.color || undefined,
    custom_class: customClass,
    has_custom_class: customClass.length > 0,
  };
};

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
    <div className="mx-auto my-4 w-full lg:w-3/4">
      <Card>
        <div className="mb-2 flex items-center">
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
            className="flex-grow rounded-md border border-gray-300 p-2"
            disabled={!!is_silenced}
            enterKeyHint="send"
          />
        </div>
        <div
          ref={listRef}
          className="h-96 w-full overflow-y-auto rounded-md bg-gray-700 p-2 dark:bg-gray-800"
        >
          <ul className="space-y-2">
            {chat.map((row, idx) => {
              const rowView = buildChatRowViewModel(row);

              return (
                <li key={idx}>
                  {rowView.has_sender ? (
                    <span
                      className={clsx(
                        'cursor-pointer font-bold underline',
                        rowView.custom_class
                      )}
                      onClick={() =>
                        handleStartPrivateMessage(rowView.sender_name)
                      }
                      onKeyDown={handleOnKeyDown(rowView.sender_name)}
                      role="button"
                      tabIndex={0}
                    >
                      {rowView.location_tag} {rowView.name_label}
                      {': '}
                    </span>
                  ) : (
                    rowView.name_label.length > 0 && (
                      <span className={clsx('font-bold', rowView.custom_class)}>
                        {rowView.location_tag} {rowView.name_label}
                        {': '}
                      </span>
                    )
                  )}

                  <span
                    style={{ color: rowView.color }}
                    className={clsx(
                      rowView.has_custom_class && rowView.custom_class,
                      'pl-2'
                    )}
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
