import React from 'react';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

interface CraftingIntroductionProps {
  onAcknowledge: () => void;
}

const CraftingIntroduction = ({ onAcknowledge }: CraftingIntroductionProps) => {
  return (
    <div className="prose dark:prose-invert">
      <h2>Crafting</h2>
      <p>
        Crafting items in Tlessa is vitally important to your own progression.
      </p>
      <p>
        At first you might craft items you can get from the shop. As you level
        your crafting skill, you will unlock weapons, armour, rings, and spells
        beyond what the shop sells.
      </p>
      <p>
        High-level items that you craft can later be traded for more powerful
        gear.
      </p>
      <Button
        on_click={onAcknowledge}
        label="I understand"
        variant={ButtonVariant.PRIMARY}
      />
    </div>
  );
};

export default CraftingIntroduction;
