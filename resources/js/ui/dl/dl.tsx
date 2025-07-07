import React from 'react';

import DlProps from 'ui/dl/types/dl-props';

const Dl = ({ children }: DlProps) => (
  <dl className="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-2">
    {children}
  </dl>
);

export default Dl;
