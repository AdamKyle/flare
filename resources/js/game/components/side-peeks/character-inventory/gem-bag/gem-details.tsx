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
      <div className="flex flex-col gap-4 px-4">
        <div>
          <h2 className={clsx('text-xl font-semibold', itemColor)}>
            {gem.name}
          </h2>
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Tier {gem.tier}
          </p>
        </div>
        <Separator />
        <CharacterGemModifierList modifiers={gem.modifiers} />
      </div>
    </>
  );
};

export default GemDetails;
