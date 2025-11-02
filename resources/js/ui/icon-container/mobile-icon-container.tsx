import React, { ReactNode } from 'react';

import HorizontalIcons from './partials/mobile-icon-container/horizontal-icons';
import VerticalSideIcons from './partials/mobile-icon-container/vertical-side-icons';

import MobileIconContainerProps from 'ui/icon-container/types/mobile-icon-container-props';

export const MobileIconContainer = ({
  icon_buttons,
}: MobileIconContainerProps): ReactNode => {
  return (
    <>
      <div className="3xl:block hidden">
        <VerticalSideIcons icon_buttons={icon_buttons} />
      </div>
      <div className="3xl:hidden hidden md:block">
        <HorizontalIcons icon_buttons={icon_buttons} />
      </div>
    </>
  );
};
