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
      <i
        className="fas fa-flask text-wisp-pink-500 dark:text-wisp-pink-400 animate-pulse motion-reduce:animate-none"
        aria-hidden="true"
      />
      <span className="sr-only">Active Alchemy boons</span>
    </span>
  );
};

export default CharacterActiveBoonIndicator;
