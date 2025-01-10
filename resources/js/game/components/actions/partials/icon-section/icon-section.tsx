import { AnimatePresence } from 'framer-motion';
import React, { ReactNode } from 'react';

import { MotionDiv } from './components/motion-div';
import CharacterCard from '../floating-cards/character-details/character-card';
import { useManageCharacterCardVisibility } from '../floating-cards/character-details/hooks/use-manage-character-card-visibility';
import ChatCard from '../floating-cards/chat-section/chat-card';
import { useManageChatCardVisibility } from '../floating-cards/chat-section/hooks/use-manage-chat-card-visibility';
import CraftingCard from '../floating-cards/crafting-section/crafting-card';
import { useManageCraftingCardVisibility } from '../floating-cards/crafting-section/hooks/use-manage-crafting-card-visibility';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import IconButton from 'ui/buttons/icon-button';
import IconContainer from 'ui/icon-container/icon-container';

export const IconSection = (): ReactNode => {
  const { showCharacterCard, openCharacterCard } =
    useManageCharacterCardVisibility();

  const { showCraftingCard, openCraftingCard } =
    useManageCraftingCardVisibility();

  const { showChatCard, openChatCard } = useManageChatCardVisibility();

  return (
    <IconContainer>
      <IconButton
        label="Character"
        icon={<i className="ra ra-player text-sm" aria-hidden="true"></i>}
        variant={ButtonVariant.PRIMARY}
        on_click={openCharacterCard}
        additional_css="w-full lg:w-auto"
      />
      <IconButton
        label="Craft"
        icon={<i className="ra ra-anvil text-sm" aria-hidden="true"></i>}
        variant={ButtonVariant.PRIMARY}
        on_click={openCraftingCard}
        additional_css="w-full lg:w-auto"
      />
      <IconButton
        label="Chat"
        icon={<i className="far fa-comments text-sm" aria-hidden="true"></i>}
        variant={ButtonVariant.PRIMARY}
        on_click={openChatCard}
        additional_css="w-full lg:w-auto"
      />
      <IconButton
        label="Map"
        icon={<i className="ra ra-compass text-sm" aria-hidden="true"></i>}
        variant={ButtonVariant.PRIMARY}
        on_click={() => {}}
        additional_css="w-full lg:w-auto"
      />

      <AnimatePresence>
        {showCharacterCard && (
          <MotionDiv>
            <CharacterCard />
          </MotionDiv>
        )}

        {showCraftingCard && (
          <MotionDiv>
            <CraftingCard />
          </MotionDiv>
        )}

        {showChatCard && (
          <MotionDiv>
            <ChatCard />
          </MotionDiv>
        )}
      </AnimatePresence>
    </IconContainer>
  );
};
