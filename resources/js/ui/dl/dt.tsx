import clsx from 'clsx';
import React from 'react';

import DtProps from 'ui/dl/types/dt-props';

const Dt = ({ children, text_class: textClass }: DtProps) => {
  return (
    <dt
      className={clsx(
        'flex min-w-0 items-center gap-2 font-medium break-words',
        textClass ?? 'text-gray-700 dark:text-gray-300'
      )}
    >
      {children}
    </dt>
  );
};

export default Dt;
