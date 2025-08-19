import React from 'react';

import DlProps from 'ui/dl/types/dl-props';

const Dl = ({ children }: DlProps) => (
  <dl className="grid grid-cols-[1fr_auto] items-center gap-x-4 gap-y-2">
    {children}
  </dl>
);

export default Dl;
