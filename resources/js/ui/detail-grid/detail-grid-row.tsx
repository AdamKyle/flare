import clsx from 'clsx';
import React, { ReactNode } from 'react';

import DetailGridRowProps from './types/detail-grid-row-props';

const DetailGridRow = ({
  children,
  additional_css: additionalCss,
  single_column = false,
}: DetailGridRowProps): ReactNode => (
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

export default DetailGridRow;
