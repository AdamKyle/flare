import React from 'react';

import CardFrontProps from './types/card-front-props';

const CardFront = ({ children }: CardFrontProps) => {
  return (
    <div
      className="absolute inset-0 flex h-full flex-col items-center justify-center rounded-t-xl border-x border-t border-gray-200/80 bg-gray-50 px-4 text-center text-gray-900 shadow-sm transition-shadow group-hover:shadow-md dark:border-gray-600/70 dark:bg-gray-800 dark:text-gray-50"
      style={{
        backfaceVisibility: 'hidden',
        WebkitBackfaceVisibility: 'hidden',
        transform: 'rotateY(0deg)',
      }}
    >
      {children}
    </div>
  );
};

export default CardFront;
