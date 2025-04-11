import React from 'react';

import { useDynamicComponentVisibility } from './hooks/use-manage-side-peek-visibility';

import SidePeek from 'ui/side-peek/side-peek';

const BaseSidePeek = () => {
  const { ComponentToRender, componentProps, closeSidePeek } =
    useDynamicComponentVisibility();

  return (
    <SidePeek
      title={componentProps.title}
      is_open={componentProps.is_open}
      on_close={closeSidePeek}
      allow_clicking_outside={componentProps.allow_clicking_outside}
    >
      <div className="h-full flex flex-col">
        {ComponentToRender && <ComponentToRender {...componentProps} />}
      </div>
    </SidePeek>
  );
};

export default BaseSidePeek;
