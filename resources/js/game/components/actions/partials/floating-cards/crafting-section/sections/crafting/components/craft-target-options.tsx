import React, { ReactNode } from 'react';

import CraftTargetOptionsProps from './types/craft-target-options-props';

const CraftTargetOptions = ({
  canCraftForNpc,
  canCraftForEvent,
  craftForNpc,
  craftForEvent,
  onCraftForNpcChange,
  onCraftForEventChange,
}: CraftTargetOptionsProps): ReactNode => {
  const renderCraftForNpcCheckbox = () => {
    if (!canCraftForNpc) {
      return null;
    }

    return (
      <label className="flex items-center gap-2 text-sm">
        <input
          type="checkbox"
          checked={craftForNpc}
          onChange={onCraftForNpcChange}
        />
        Craft for the faction loyalty NPC
      </label>
    );
  };

  const renderCraftForEventCheckbox = () => {
    if (!canCraftForEvent) {
      return null;
    }

    return (
      <label className="flex items-center gap-2 text-sm">
        <input
          type="checkbox"
          checked={craftForEvent}
          onChange={onCraftForEventChange}
        />
        Craft for the global event
      </label>
    );
  };

  return (
    <>
      {renderCraftForNpcCheckbox()}
      {renderCraftForEventCheckbox()}
    </>
  );
};

export default CraftTargetOptions;
