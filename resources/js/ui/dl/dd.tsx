import React from 'react';

import DdProps from 'ui/dl/types/dd-props';

const Dd = ({ children }: DdProps) => {
  return (
    <dd className="text-glacier-800 dark:text-glacier-300 justify-self-end text-right">
      {children}
    </dd>
  );
};

export default Dd;
