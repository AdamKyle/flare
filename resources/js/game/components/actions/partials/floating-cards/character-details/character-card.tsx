import EventSystemDefinition from 'event-system/deffintions/event-system-definition';
import React, { ReactNode } from 'react';

import CharacterCardDetails from './character-card-details';
import FloatingCard from '../../../components/icon-section/floating-card';
import { ActionCardEvents } from '../event-types/action-cards';

import { serviceContainer } from 'service-container/core-container';

const CharacterCard = (): ReactNode => {
  const eventSystem =
    serviceContainer().fetch<EventSystemDefinition>('EventSystem');

  const handleCloseCard = () => {
    const event = eventSystem.getEventEmitter<{ [key: string]: boolean }>(
      ActionCardEvents.CLOSE_CHARACTER_CARD
    );

    event.emit(ActionCardEvents.CLOSE_CHARACTER_CARD, true);
  };

  return (
    <FloatingCard
      title="Character Name (Lvl: 5,000)"
      close_action={handleCloseCard}
    >
      <CharacterCardDetails />
    </FloatingCard>
  );
};

export default CharacterCard;
