import React from 'react';

import DtProps from 'ui/dl/types/dt-props';

const Dt = ({ children }: DtProps) => {
  return (
    <dt className="flex min-w-0 items-center gap-2 font-medium break-words text-gray-800 dark:text-gray-300">
      {children}
    </dt>
  );
};

export default Dt;
