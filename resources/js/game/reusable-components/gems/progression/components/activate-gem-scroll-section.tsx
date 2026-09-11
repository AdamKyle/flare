import React, { ReactNode, useState } from 'react';

import GemScrollPicker from './gem-scroll-picker';
import ActivateGemScrollSectionProps from './types/activate-gem-scroll-section-props';
import { useGemScrollActions } from '../api/hooks/use-gem-scroll-actions';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

type ScrollFamily = 'xp' | 'currency' | 'item';

const familyLabel = (family: ScrollFamily): string => {
  if (family === 'xp') {
    return 'XP';
  }

  if (family === 'currency') {
    return 'Currency';
  }

  return 'Item';
};

// Fill/remove for already-active Scrolls lives in ActiveGemScrollsSection; this only starts a new one.
const ActivateGemScrollSection = ({
  character_id: characterId,
  on_activated: onActivated,
}: ActivateGemScrollSectionProps): ReactNode => {
  const [selectedFamily, setSelectedFamily] = useState<ScrollFamily | null>(
    null
  );

  const {
    actingScrollId,
    successMessage,
    mutationError,
    useScroll: activateScroll,
  } = useGemScrollActions({ characterId, onSuccess: onActivated });

  const handleSelectSlot = (alchemyBagSlotId: number): void => {
    setSelectedFamily(null);
    void activateScroll(alchemyBagSlotId);
  };

  const renderFamilyButtons = (): ReactNode => (
    <div className="flex flex-wrap gap-2">
      {(['xp', 'currency', 'item'] as ScrollFamily[]).map((family) => {
        const isSelected = selectedFamily === family;

        return (
          <Button
            key={family}
            label={familyLabel(family)}
            aria_label={`${familyLabel(family)}${isSelected ? ' (selected)' : ''}`}
            variant={isSelected ? ButtonVariant.ACTIVE : ButtonVariant.ALCHEMY}
            on_click={() => setSelectedFamily(family)}
          />
        );
      })}
    </div>
  );

  const renderPicker = (): ReactNode => {
    if (!selectedFamily) {
      return null;
    }

    return (
      <GemScrollPicker
        character_id={characterId}
        gem_scroll_type={selectedFamily}
        disabled={actingScrollId !== null}
        on_select={handleSelectSlot}
      />
    );
  };

  return (
    <div className="flex flex-col gap-2">
      <h4 className="text-glacier-800 dark:text-glacier-200 text-xs font-semibold tracking-wide uppercase">
        Activate a New Gem Scroll
      </h4>
      {(mutationError || successMessage) && (
        <Alert
          variant={mutationError ? AlertVariant.DANGER : AlertVariant.SUCCESS}
        >
          {mutationError ?? successMessage}
        </Alert>
      )}
      {renderFamilyButtons()}
      {renderPicker()}
    </div>
  );
};

export default ActivateGemScrollSection;
