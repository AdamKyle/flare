import clsx from 'clsx';
import React, { ReactNode } from 'react';

import DetailGridProps from './types/detail-grid-props';

const DetailGrid = ({
  children,
  additional_css: additionalCss,
  single_column = false,
}: DetailGridProps): ReactNode => (
  <div
    className={clsx(
      'grid grid-cols-1 gap-x-8 gap-y-5',
      !single_column && 'md:grid-cols-2',
      additionalCss
    )}
  >
    {children}
  </div>
);

export default DetailGrid;
