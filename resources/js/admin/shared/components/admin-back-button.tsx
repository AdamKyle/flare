import React, { ReactNode } from 'react';

import AdminBackButtonProps from '../types/admin-back-button-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const AdminBackButton = ({
  on_click: onClick,
  label = 'Back',
}: AdminBackButtonProps): ReactNode => (
  <Button label={label} variant={ButtonVariant.DANGER} on_click={onClick} />
);

export default AdminBackButton;
