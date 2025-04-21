import React, { ReactNode } from 'react';

import { IconSection } from '../icon-section/icon-section';
import NavigationActions from './types/navigation-actions-props';

const NavigationActionsComponent = (props: NavigationActions): ReactNode => {
  return (
    <div>
      {props.isMobile ? (
        <div>
          <IconSection />
        </div>
      ) : (
        <div
        >
          <IconSection />
        </div>
      )}
    </div>
  );
};

export default NavigationActionsComponent;
