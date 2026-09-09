import clsx from 'clsx';
import React from 'react';

import DdProps from 'ui/dl/types/dd-props';

const Dd = ({ children, text_class: textClass }: DdProps) => {
  return (
    <dd
      className={clsx(
        'justify-self-end text-right',
        textClass ?? 'text-gray-900 dark:text-gray-100'
      )}
    >
      {children}
    </dd>
  );
};

export default Dd;
