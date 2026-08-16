import React, { ReactNode } from 'react';

import FloatingCardScreenStackProps from './types/floating-card-screen-stack-props';

const FloatingCardScreenStack = ({
  label,
  children,
}: FloatingCardScreenStackProps): ReactNode => (
  <div
    role="group"
    aria-label={label}
    className="relative w-full overflow-hidden"
  >
    {children}
  </div>
);

export default FloatingCardScreenStack;
