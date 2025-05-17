import React from 'react';

import { useDynamicComponentVisibility } from './hooks/use-manage-side-peek-visibility';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import SidePeek from 'ui/side-peek/side-peek';

const BaseSidePeek = () => {
  const { ComponentToRender, componentProps, closeSidePeek } =
    useDynamicComponentVisibility();

  const renderFooter = () => {
    if (
      !componentProps.has_footer ||
      !componentProps.footer_secondary_action ||
      !componentProps.footer_primary_action
    ) {
      return null;
    }

    return (
      <div className="border-t p-4 flex justify-between">
        <Button
          on_click={componentProps.footer_secondary_action}
          label={componentProps.footer_secondary_label || ''}
          variant={ButtonVariant.DANGER}
        />
        <Button
          on_click={componentProps.footer_primary_action}
          label={componentProps.footer_primary_label || ''}
          variant={ButtonVariant.PRIMARY}
        />
      </div>
    );
  };

  return (
    <SidePeek
      title={componentProps.title}
      is_open={componentProps.is_open}
      on_close={closeSidePeek}
      allow_clicking_outside={componentProps.allow_clicking_outside}
    >
      <div className="flex flex-col h-full min-h-0">
        <div className="flex-1 overflow-auto">
          {ComponentToRender && <ComponentToRender {...componentProps} />}
        </div>
        {renderFooter()}
      </div>
    </SidePeek>
  );
};

export default BaseSidePeek;
