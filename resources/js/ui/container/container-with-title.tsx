import React, { ReactNode } from 'react';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import ContainerProps from 'ui/container/types/container-props';
import Separator from 'ui/separator/separator';

const ContainerWithTitle = (props: ContainerProps): ReactNode => {
  return (
    <div className="mx-auto mt-4 w-full sm:px-2 lg:w-3/4">
      <div className="mb-4 flex items-center justify-between px-2 lg:px-0">
        <h3 className="flex items-center text-xl text-gray-800 dark:text-gray-300">
          {props.title}
        </h3>
        <div>
          <Button
            on_click={props.manageSectionVisibility}
            label="Close"
            variant={ButtonVariant.DANGER}
          />
        </div>
      </div>

      <Separator />
      {props.children}
    </div>
  );
};

export default ContainerWithTitle;
