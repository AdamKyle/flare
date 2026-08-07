import clsx from 'clsx';
import React from 'react';

import GemDetailsProps from './types/gem-details-props';
import { getGemSlotTitleTextColor } from '../../../character-sheet/partials/character-inventory/styles/gem-slot-styles';
import GemDetailsContent from '../../../../reusable-components/gem/gem-details-content';

import Separator from 'ui/separator/separator';

const GemDetails = ({ gem }: GemDetailsProps) => {
  const itemColor = getGemSlotTitleTextColor(gem);

  return (
    <>
      <div className="flex flex-col gap-2 px-4">
        <h2 className={clsx('my-2 text-lg', itemColor)}>{gem.name}</h2>
        <Separator />

        <GemDetailsContent gem={gem} />
      </div>
    </>
  );
};

export default GemDetails;
