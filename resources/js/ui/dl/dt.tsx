import React from 'react';

import DtProps from 'ui/dl/types/dt-props';

const Dt = ({ children }: DtProps) => {
  return (
    <dt className="text-glacier-800 dark:text-glacier-300 flex min-w-0 items-center gap-2 font-medium break-words">
      {children}
    </dt>
  );
};

export default Dt;
