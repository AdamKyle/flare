import { AnimatePresence } from 'framer-motion';
import React, { ReactNode, useState } from 'react';

import CharacterGemDefinition from '../../../../../../api-definitions/gems/character-gem-definition';
import DefinitionRow from '../../../../../../reusable-components/viewable-sections/definition-row';
import Section from '../../../../../../reusable-components/viewable-sections/section';
import InventoryStackBody from '../../../components/inventory-stack-body';
import GemDetails from '../../../gem-bag/gem-details';
import SocketsSectionProps from '../../types/partials/item-view/sockets-section-props';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';
import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import GeneralToolTip from 'ui/tool-tips/general-tool-tip';

const SOCKET_INFORMATION =
  'Sockets are slots on equippable items where Character Gems can be attached. Attached Gems contribute their effects and modifiers according to the normal Gem rules while the item is equipped.';

const SocketsSection = ({ total, sockets }: SocketsSectionProps): ReactNode => {
  const [selectedGem, setSelectedGem] = useState<CharacterGemDefinition | null>(
    null
  );

  if (total <= 0) {
    return null;
  }

  const occupiedSockets = sockets.filter(
    (socket): socket is typeof socket & { gem: CharacterGemDefinition } =>
      socket.gem !== null
  );

  const title = (
    <span className="flex w-full items-center justify-between gap-3">
      <span>Sockets</span>
      <span className="flex items-center gap-2 text-gray-700 dark:text-gray-300">
        <span className="tabular-nums">{total}</span>
        <GeneralToolTip label="Sockets" message={SOCKET_INFORMATION} />
      </span>
    </span>
  );

  const renderGemDetails = (): ReactNode => {
    if (selectedGem === null) {
      return null;
    }

    return (
      <StackedCard
        on_close={() => setSelectedGem(null)}
        aria_label="Gem Details"
        content_mode={StackedCardContentMode.FULL_BLEED}
      >
        <InventoryStackBody>
          <GemDetails gem={selectedGem} />
        </InventoryStackBody>
      </StackedCard>
    );
  };

  return (
    <>
      <Section title={title}>
        {occupiedSockets.map((socket) => (
          <DefinitionRow
            key={socket.id}
            left={`Socket ${socket.position}:`}
            right={
              <LinkButton
                label={socket.gem.name}
                aria_label={`View ${socket.gem.name} Gem details`}
                variant={ButtonVariant.SUCCESS}
                on_click={() => setSelectedGem(socket.gem)}
              />
            }
          />
        ))}
      </Section>
      <AnimatePresence mode="wait">{renderGemDetails()}</AnimatePresence>
    </>
  );
};

export default SocketsSection;
