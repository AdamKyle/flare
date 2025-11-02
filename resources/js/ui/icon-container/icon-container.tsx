import React, { ReactNode } from 'react';

import IconContainerProps from './types/icon-container-props';

const IconContainer = (props: IconContainerProps): ReactNode => {
  return (
    <div className="mt-4 hidden w-full items-center justify-between space-y-0 sm:flex md:mx-auto lg:mt-3 lg:mr-4 lg:w-10 lg:flex-col lg:items-start lg:justify-start lg:space-y-2">
      <div className="flex w-full space-x-2 lg:w-auto lg:flex-col lg:space-y-2 lg:space-x-0">
        {props.children}
      </div>
    </div>
  );
};

export default IconContainer;
