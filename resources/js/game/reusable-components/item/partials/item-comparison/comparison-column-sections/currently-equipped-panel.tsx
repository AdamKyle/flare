import React from 'react';

import CurrentlyEquippedPanelProps from '../../../types/partials/item-comparison/comparison-column-sections/currently-equipped-panel-props';
import { getPositionLabel } from '../../../utils/item-comparison';

const CurrentlyEquippedPanel = ({
  position,
  equippedAffixName,
  type,
  isTwoHanded,
}: CurrentlyEquippedPanelProps) => {

  const renderType = () => {
    if (!type) {
      return null;
    }

    return (
      <>
        <span className="mx-2 text-gray-500 dark:text-gray-400">•</span>
        <span className="font-medium">Type:</span>{' '}
        <span className="capitalize">{type}</span>
      </>
    );
  }

  const renderTwoHanded = () => {
    if (!isTwoHanded) {
      return null;
    }

    return (
      <>
        <span className="mx-2 text-gray-500 dark:text-gray-400">•</span>
        <span className="font-medium">Two-Handed</span>
      </>
    )
  }

  return (
    <div className="mb-4 rounded-md border border-gray-200/60 bg-gray-100/30 px-3 py-2 dark:border-gray-700/60 dark:bg-gray-800/30">
      <div className="mb-1 text-[11px] font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
        Currently Equipped
      </div>

      <div className="text-sm leading-snug text-gray-900 dark:text-gray-100 break-words">
        <span className="font-medium">
          Replacing {getPositionLabel(position)}:
        </span>{' '}
        <span className="italic">{equippedAffixName}</span>
        {renderType()}
        {renderTwoHanded()}
      </div>
    </div>
  );
};

export default CurrentlyEquippedPanel;
