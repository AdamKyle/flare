import React, { ReactNode } from 'react';

import ContainerWrapperProps from 'ui/container/types/container-wrapper-props';

const ContainerWrapper = (props: ContainerWrapperProps): ReactNode => {
  return (
    <div className="w-full mt-4 px-4 py-6 xl:mt-0 xl:w-2/3 xl:mx-auto">
      {props.children}
    </div>
  );
};

export default ContainerWrapper;
