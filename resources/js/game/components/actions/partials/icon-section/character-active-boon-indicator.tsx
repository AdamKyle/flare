import React, { ReactNode } from 'react';

interface CharacterActiveBoonIndicatorProps {
  active: boolean;
}

const CharacterActiveBoonIndicator = ({
  active,
}: CharacterActiveBoonIndicatorProps): ReactNode => {
  if (!active) {
    return null;
  }

  return (
    <span className="inline-flex items-center">
      <span
        className="bg-wisp-pink-500 dark:bg-wisp-pink-400 inline-block h-2 w-2 rounded-full"
        aria-hidden="true"
      />
      <span className="sr-only">Active Alchemy boons</span>
    </span>
  );
};

export default CharacterActiveBoonIndicator;
