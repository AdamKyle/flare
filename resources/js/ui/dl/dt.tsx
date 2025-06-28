import React from 'react';

import DtProps from 'ui/dl/types/dt-props';

const Dt = ({ children }: DtProps) => {
  return (
    <dt className="flex items-center space-x-2 text-gray-800 dark:text-gray-300">
      {children}
    </dt>
  );
};

export default Dt;
