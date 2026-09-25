import clsx from 'clsx';
import React, { useCallback, useRef, useState } from 'react';

import MessagesProps from './definitions/message-props';
import {
  buildChatMessageClassName,
  resolveChatMessageColor,
} from './styles/chat-message-styles';
import ChatType from '../../../../api-definitions/chat/chat-message-definition';
import { formatMessageTimestamp } from '../../utils/format-message-timestamp';
import { EventMessageTypes } from '../../websockets/enums/event-message-types';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Card from 'ui/cards/card';

interface ChatRowViewModel {
  is_creator_message: boolean;
  sender_name: string;
  has_sender: boolean;
  name_label: string;
  location_tag: string;
  color: string | undefined;
  sender_class_name: string;
  message_class_name: string;
  message: string;
  created_at: string | null;
}

const CREATOR_MESSAGE_PREFIX = 'The Creator Bellows Across the Heavens:';

const DEFAULT_SENDER_CLASS_NAME = 'text-gray-200';

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

  return {
    is_creator_message: row.type === EventMessageTypes.CREATOR_MESSAGE,
    sender_name: senderName,
    has_sender: senderName.length > 0,
    name_label: nameLabel,
    location_tag: row.map_name ? `[${row.map_name} (${coords})]` : '',
    color: resolveChatMessageColor(row),
    sender_class_name: row.custom_class || DEFAULT_SENDER_CLASS_NAME,
    message_class_name: buildChatMessageClassName(row),
    message: row.message,
    created_at: row.created_at,
  };
};

const Messages = ({
  is_silenced,
  can_talk_again_at,
  chat,
  set_tab_to_updated,
  push_silenced_message,
  push_error_message,
  on_send,
  can_start_private_message = true,
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

    on_send(text);
    set_tab_to_updated('chat');
    setText('');
  }, [
    is_silenced,
    canSend,
    on_send,
    push_error_message,
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

  const chatRows = chat.map(buildChatRowViewModel);

  const renderTimestamp = (rowView: ChatRowViewModel) => {
    if (rowView.created_at === null) {
      return null;
    }

    return (
      <time
        dateTime={rowView.created_at}
        className="mr-2 text-xs text-gray-400 dark:text-gray-500"
      >
        {formatMessageTimestamp(rowView.created_at)}
      </time>
    );
  };

  const renderSender = (rowView: ChatRowViewModel) => {
    if (rowView.has_sender && can_start_private_message) {
      return (
        <button
          type="button"
          className={clsx(
            'focus:ring-danube-300 cursor-pointer text-left font-bold underline focus:ring-2 focus:outline-none',
            rowView.sender_class_name
          )}
          onClick={() => handleStartPrivateMessage(rowView.sender_name)}
          aria-label={`Send a private message to ${rowView.sender_name}`}
        >
          {rowView.location_tag} {rowView.name_label}
          {': '}
        </button>
      );
    }

    if (rowView.name_label.length === 0) {
      return null;
    }

    return (
      <span className={clsx('font-bold', rowView.sender_class_name)}>
        {rowView.location_tag} {rowView.name_label}
        {': '}
      </span>
    );
  };

  const renderCreatorRow = (rowView: ChatRowViewModel, rowIndex: number) => (
    <li key={rowIndex} className="break-words">
      {renderTimestamp(rowView)}
      <span className={rowView.message_class_name}>
        {CREATOR_MESSAGE_PREFIX} {rowView.message}
      </span>
    </li>
  );

  const renderChatRow = (rowView: ChatRowViewModel, rowIndex: number) => {
    if (rowView.is_creator_message) {
      return renderCreatorRow(rowView, rowIndex);
    }

    return (
      <li key={rowIndex} className="break-words">
        {renderTimestamp(rowView)}
        {renderSender(rowView)}

        <span
          style={{ color: rowView.color }}
          className={clsx(rowView.message_class_name, 'pl-2')}
        >
          {rowView.message}
        </span>
      </li>
    );
  };

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
            aria-label="Chat message"
            placeholder={
              is_silenced && can_talk_again_at
                ? `Silenced until ${can_talk_again_at}`
                : 'Type your message'
            }
            value={text}
            onChange={(event) => setText(event.target.value)}
            onKeyDown={handleInputKeyDown}
            className="flex-grow rounded-md border border-gray-300 p-2 dark:border-gray-600"
            disabled={!!is_silenced}
            enterKeyHint="send"
          />
        </div>
        <div
          ref={listRef}
          className="h-96 w-full overflow-y-auto rounded-md bg-gray-700 p-2 dark:bg-gray-800"
        >
          <ul className="space-y-2">{chatRows.map(renderChatRow)}</ul>
        </div>
      </Card>
    </div>
  );
};

export default Messages;
