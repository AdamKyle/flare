import React, { ReactNode } from 'react';

import IconContainerProps from './types/icon-container-props';

const IconContainer = (props: IconContainerProps): ReactNode => {
  return (
    <div className="hidden sm:flex lg:flex-col items-center md:mx-auto w-full lg:w-10 justify-between lg:items-start lg:mr-4 lg:justify-start lg:mt-3 mt-4 space-y-0 lg:space-y-2">
      <div className="flex lg:flex-col w-full lg:w-auto lg:space-y-2 space-x-2 lg:space-x-0">
        {props.children}
      </div>
    </div>
  );
};

export default IconContainer;
