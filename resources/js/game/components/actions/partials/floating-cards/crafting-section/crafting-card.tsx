import React, { ReactNode } from 'react';

import { useManageCraftingCardVisibility } from './hooks/use-manage-crafting-card-visibility';
import FloatingCard from '../../../components/icon-section/floating-card';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const CraftingCard = (): ReactNode => {
  const { closeCraftingCard } = useManageCraftingCardVisibility();

  return (
    <FloatingCard title="Crafting" close_action={closeCraftingCard}>
      <Button
        label="Craft"
        on_click={() => {}}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
      />
      <Button
        label="Enchant"
        on_click={() => {}}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
      />
      <Button
        label="Alchemy"
        on_click={() => {}}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
      />
      <Button
        label="Trinketry"
        on_click={() => {}}
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
    </FloatingCard>
  );
};

export default CraftingCard;
