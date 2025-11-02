import React, { ReactNode } from 'react';

import ContainerWrapperProps from 'ui/container/types/container-wrapper-props';

const ContainerWrapper = (props: ContainerWrapperProps): ReactNode => {
  return (
    <div className="mt-4 w-full px-4 py-6 xl:mx-auto xl:mt-0 xl:w-2/3">
      {props.children}
    </div>
  );
};

export default ContainerWrapper;
