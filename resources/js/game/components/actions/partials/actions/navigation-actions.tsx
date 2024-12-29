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
          style={{
            position: 'absolute',
            top: `${props.scrollY + 10}px`,
            left: '10px',
            transition: 'top 0.2s',
          }}
        >
          <IconSection />
        </div>
      )}
    </div>
  );
};

export default NavigationActionsComponent;
