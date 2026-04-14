import React from 'react';

import BaseSectionProps from './types/base-section-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const CraftingSection = ({ setActiveCraftingType }: BaseSectionProps) => {
  return (
    <div className={'prose dark:prose-invert'}>
      <h2>Crafting</h2>
      <p>
        Crafting items in Tlessa is vitally important to your own progression.
      </p>
      <p>
        At first you might craft items you can get fro the shop. But as you
        level up your crafting skill you will unlock weapons, armour, rings and
        even spells beyond what the shop sells
      </p>
      <p>
        High level items that you craft can then be traded later on for more
        powerful gear.
      </p>
      <Button
        on_click={() => {}}
        label={'I understand'}
        variant={ButtonVariant.PRIMARY}
      />
    </div>
  );
};

export default CraftingSection;
