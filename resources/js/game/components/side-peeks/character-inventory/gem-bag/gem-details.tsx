import clsx from 'clsx';
import React from 'react';

import GemDetailsProps from './types/gem-details-props';
import CharacterGemModifierList from '../../../../reusable-components/character-gem/character-gem-modifier-list';
import { getGemSlotTitleTextColor } from '../../../character-sheet/partials/character-inventory/styles/gem-slot-styles';

import Separator from 'ui/separator/separator';

const GemDetails = ({ gem }: GemDetailsProps) => {
  const itemColor = getGemSlotTitleTextColor(gem);

  return (
    <>
      <div className="flex flex-col gap-2 px-4">
        <h2 className={clsx('my-2 text-lg', itemColor)}>{gem.name}</h2>
        <Separator />

        <p>Tier {gem.tier}</p>
        <CharacterGemModifierList modifiers={gem.modifiers} />
      </div>
    </>
  );
};

export default GemDetails;
