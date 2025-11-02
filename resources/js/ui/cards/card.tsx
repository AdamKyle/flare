import React, { ReactNode } from 'react';

import CardProps from './types/card-props';

const Card = (props: CardProps): ReactNode => {
  return (
    <div className="rounded-sm border-1 border-gray-300 bg-white dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
      <div className="p-3 md:p-4 xl:p-6">{props.children}</div>
    </div>
  );
};

export default Card;
