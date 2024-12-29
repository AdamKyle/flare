import React, { ReactNode } from 'react';

import ContainerProps from 'ui/container/types/container-props';

const Container = (props: ContainerProps): ReactNode => {
  return (
    <div className="w-full mt-4 md:mt-0 md:w-2/3 md:mx-auto md:px-4 md:py-6">
      {props.children}
    </div>
  );
};

export default Container;
