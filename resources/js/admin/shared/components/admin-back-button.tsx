import React, { ReactNode } from 'react';

import AdminBackButtonProps from '../types/admin-back-button-props';

const AdminBackButton = ({
  on_click: onClick,
  label = 'Back',
}: AdminBackButtonProps): ReactNode => (
  <button
    type="button"
    onClick={onClick}
    className="focus-visible:ring-glacier-400 border-glacier-300 text-glacier-700 hover:bg-glacier-50 dark:border-glacier-700 dark:bg-glacier-950 dark:text-glacier-200 dark:hover:bg-glacier-900 rounded-md border bg-white px-3 py-1.5 text-sm font-medium focus:outline-none focus-visible:ring-2"
  >
    {label}
  </button>
);

export default AdminBackButton;
