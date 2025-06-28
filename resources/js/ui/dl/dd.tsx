import React from 'react';

import DdProps from 'ui/dl/types/dd-props';

const Dd = ({ children }: DdProps) => {
  return <dd className="text-gray-800 dark:text-gray-300">{children}</dd>;
};

export default Dd;
