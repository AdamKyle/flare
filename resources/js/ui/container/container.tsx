import React, { ReactNode } from 'react';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import ContainerProps from 'ui/container/types/container-props';
import Separator from 'ui/seperatror/separator';

const Container = (props: ContainerProps): ReactNode => {
  return (
    <div className="w-full mt-4 xl:mt-0 xl:w-2/3 xl:mx-auto xl:px-4 xl:py-6">
      <div className="flex items-center justify-between mb-4">
        <h3 className="flex items-center">{props.title}</h3>
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

export default Container;
