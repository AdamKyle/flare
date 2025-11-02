import React, { ReactNode } from 'react';

import AttackButtonsContainerProps from './types/attack-buttons-container-props';

const AttackButtonsContainer = (
  props: AttackButtonsContainerProps
): ReactNode => {
  return (
    <div className="mx-auto mt-4 flex w-full flex-col items-center justify-center gap-x-3 gap-y-4 text-lg leading-none sm:flex-row">
      {props.children}
    </div>
  );
};

export default AttackButtonsContainer;
