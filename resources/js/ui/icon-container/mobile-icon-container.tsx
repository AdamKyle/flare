import React, { ReactNode } from 'react';

import HorizontalIcons from './partials/mobile-icon-container/horizontal-icons';
import VerticalSideIcons from './partials/mobile-icon-container/vertical-side-icons';

import MobileIconContainerProps from 'ui/icon-container/types/mobile-icon-container-props';

export const MobileIconContainer = ({
  icon_buttons,
}: MobileIconContainerProps): ReactNode => {
  return (
    <>
      <div className="hidden md:block lg:hidden">
        <VerticalSideIcons icon_buttons={icon_buttons} />
      </div>
      <div className="hidden lg:block">
        <HorizontalIcons icon_buttons={icon_buttons} />
      </div>
    </>
  );
};
