import React, { ReactNode } from 'react';

import ContainerWrapperProps from 'ui/container/types/container-wrapper-props';

const ContainerWrapper = (props: ContainerWrapperProps): ReactNode => {
  return (
    <div className="w-full mt-4 xl:mt-0 xl:w-2/3 xl:mx-auto xl:px-4 xl:py-6">
      {props.children}
    </div>
  );
};

export default ContainerWrapper;
