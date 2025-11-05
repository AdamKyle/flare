import React, { ReactNode } from 'react';

import ContainerWrapperProps from 'ui/container/types/container-wrapper-props';

const WideContainerWrapper = (props: ContainerWrapperProps): ReactNode => {
  return (
    <div className="mt-4 w-full px-4 py-6 md:mx-auto md:w-3/4">
      {props.children}
    </div>
  );
};

export default WideContainerWrapper;
