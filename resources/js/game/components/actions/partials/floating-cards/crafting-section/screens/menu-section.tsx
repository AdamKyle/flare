import React from 'react';

import BaseSectionProps from './types/base-section-props';
import { CraftingTypes } from '../enums/crafting-types';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const MenuSection = ({ setActiveCraftingType }: BaseSectionProps) => {
  return (
    <>
      <Button
        label="Craft"
        on_click={() => setActiveCraftingType(CraftingTypes.CRAFT)}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
      />
      <Button
        label="Enchant"
        on_click={() => setActiveCraftingType(CraftingTypes.ENCHANT)}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
      />
      <Button
        label="Alchemy"
        on_click={() => setActiveCraftingType(CraftingTypes.ALCHEMY)}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
      />
      <Button
        label="Trinketry"
        on_click={() => setActiveCraftingType(CraftingTypes.TRINKETS)}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
      />
      <Button
        label="Gem Crafting"
        on_click={() => {}}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
      />
      <Button
        label="Queen of Hearts"
        on_click={() => {}}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
      />
      <Button
        label="Seer Camp"
        on_click={() => {}}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
      />
      <Button
        label="Work Bench"
        on_click={() => {}}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
      />
      <Button
        label="Labyrinth Oracle"
        on_click={() => {}}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
      />
    </>
  );
};

export default MenuSection;
