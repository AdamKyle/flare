import React, { ReactNode } from 'react';

import HealthBarContainerProps from './types/health-bar-container-props';

const HealthBarContainer = (props: HealthBarContainerProps): ReactNode => {
  return (
    <div
      className="
                w-full mx-auto mt-4 flex items-center justify-center
                gap-x-3 text-lg leading-none
            "
    >
      <div className="w-full">{props.children}</div>
    </div>
  );
};

export default HealthBarContainer;
