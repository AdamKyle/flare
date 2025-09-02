import React, { ReactNode } from 'react';

import CardProps from './types/card-props';

const Card = (props: CardProps): ReactNode => {
  return (
    <div className="bg-white rounded-sm dark:bg-gray-800 dark:text-gray-400">
      <div className="p-6">{props.children}</div>
    </div>
  );
};

export default Card;
