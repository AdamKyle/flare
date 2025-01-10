import React, { ReactNode } from 'react';

import IconButtonDefinition from 'ui/buttons/definitions/icon-button-definition';
import IconButton from 'ui/buttons/icon-button';
import MobileIconContainerProps from 'ui/icon-container/types/mobile-icon-container-props';

const HorizontalIcons = ({
  icon_buttons,
}: MobileIconContainerProps): ReactNode => {
  const renderButtons = () => {
    return icon_buttons.map((inventoryButton: IconButtonDefinition) => {
      return (
        <IconButton
          label={inventoryButton.label}
          icon={<i className={inventoryButton.icon} aria-hidden="true"></i>}
          variant={inventoryButton.variant}
          on_click={() => {
            inventoryButton.onClick();
          }}
          additional_css={inventoryButton.additionalCss}
        />
      );
    });
  };

  return (
    <div className="relative left-0 top-4 flex flex-row items-center space-x-4 mb-16">
      {renderButtons()}
    </div>
  );
};

export default HorizontalIcons;
