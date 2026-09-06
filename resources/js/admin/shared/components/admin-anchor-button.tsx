import clsx from 'clsx';
import React, { ReactNode } from 'react';

import AdminAnchorButtonProps from '../types/admin-anchor-button-props';

import { baseStyles } from 'ui/buttons/styles/button/base-styles';
import { variantStyles } from 'ui/buttons/styles/button/variant-styles';

const AdminAnchorButton = ({
  href,
  label,
  variant,
  aria_label: ariaLabel,
}: AdminAnchorButtonProps): ReactNode => (
  <a
    href={href}
    className={clsx(baseStyles(), variantStyles(variant), 'inline-block')}
    aria-label={ariaLabel || label}
  >
    {label}
  </a>
);

export default AdminAnchorButton;
