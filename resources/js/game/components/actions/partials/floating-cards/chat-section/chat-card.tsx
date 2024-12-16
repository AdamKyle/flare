import EventSystemDefinition from 'event-system/deffintions/event-system-definition';
import React, { ReactNode } from 'react';

import FloatingCard from '../../../components/icon-section/floating-card';
import { ActionCardEvents } from '../EventTypes/action-cards';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

import { serviceContainer } from 'service-container/core-container';

const ChatCard = (): ReactNode => {
  const eventSystem =
    serviceContainer().fetch<EventSystemDefinition>('EventSystem');

  const handleCloseCard = () => {
    const event = eventSystem.getEventEmitter<{ [key: string]: boolean }>(
      ActionCardEvents.CLOSE_CHAT_CARD
    );

    event.emit(ActionCardEvents.CLOSE_CHAT_CARD, true);
  };

  return (
    <FloatingCard title="Chat" close_action={handleCloseCard}>
      <div className="flex items-center mb-2">
        <Button
          label="Send"
          on_click={() => {}}
          variant={ButtonVariant.PRIMARY}
          additional_css="mr-2"
        />
        <input
          type="text"
          placeholder="Type your message"
          className="flex-grow border border-gray-300 rounded-md p-2"
        />
      </div>
      <div className="bg-gray-700 dark:bg-gray-800 p-2 w-full h-96 overflow-y-auto rounded-md text-gray-400">
        <ul className="space-y-4">
          <li>
            <span className="underline font-bold">
              [SUR: xxxx/yyyy] Character Name
            </span>
            : Message here...
          </li>
          <li>
            <span className="underline font-bold">
              [LABY: xxxx/yyyy] Other Character Name
            </span>
            : In the quiet town of Elderville, nestled between rolling hills and
            lush forests, a sense of calm enveloped the streets. The sun dipped
            low in the sky, casting a golden hue over the cobblestone paths.
            Children played in the park, laughter echoing through the air as
            families gathered for evening picnics. The aroma of freshly baked
            bread wafted from the local bakery, mingling with the scent of
            blooming flowers. As the stars began to twinkle, a gentle breeze
            carried the promise of a peaceful night, inviting all to pause and
            reflect on the beauty surrounding them.
          </li>
          <li>
            <span className="underline font-bold">
              [HELL: xxxx/yyyy] RandomUerName
            </span>
            : In the heart of the city, a small coffee shop buzzed with energy.
            Baristas crafted lattes with intricate foam designs while patrons
            enjoyed their drinks, some lost in books and others engaged in
            animated conversations. The scent of fresh coffee filled the air,
            creating a warm atmosphere.
          </li>
        </ul>
      </div>
    </FloatingCard>
  );
};

export default ChatCard;
