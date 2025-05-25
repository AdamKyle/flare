import React from 'react';

import { useDynamicComponentVisibility } from './hooks/use-manage-side-peek-visibility';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import SidePeek from 'ui/side-peek/side-peek';

const BaseSidePeek = () => {
  const { ComponentToRender, componentProps, closeSidePeek } =
    useDynamicComponentVisibility();

  const handleSecondaryActionClick = () => {
    closeSidePeek();

    if (componentProps.footer_secondary_action) {
      componentProps.footer_secondary_action();
    }
  };

  const renderFooterSecondaryAction = () => {
    return (
      <Button
        on_click={handleSecondaryActionClick}
        label={componentProps.footer_secondary_label || 'Cancel'}
        variant={ButtonVariant.DANGER}
      />
    );
  };

  const renderFooterPrimaryAction = () => {
    if (!componentProps.footer_primary_action) {
      return;
    }

    return (
      <Button
        on_click={componentProps.footer_primary_action}
        label={componentProps.footer_primary_label || ''}
        variant={ButtonVariant.PRIMARY}
      />
    );
  };

  const renderFooter = () => {
    if (!componentProps.has_footer) {
      return null;
    }

    return (
      <div className="border-t p-4 flex justify-between">
        {renderFooterSecondaryAction()}
        {renderFooterPrimaryAction()}
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
