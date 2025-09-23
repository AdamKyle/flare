import React from 'react';

import DtProps from 'ui/dl/types/dt-props';

const Dt = ({ children }: DtProps) => {
  return (
    <dt className="flex items-center gap-2 min-w-0 break-words text-gray-800 dark:text-gray-300 font-medium">
      {children}
    </dt>
  );
};

export default Dt;
