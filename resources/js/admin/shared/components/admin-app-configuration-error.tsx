import React, { ReactNode } from 'react';

import AdminAppConfigurationErrorProps from '../types/admin-app-configuration-error-props';

import ApiErrorAlert from 'api-handler/components/api-error-alert';

const AdminAppConfigurationError = ({
  message,
}: AdminAppConfigurationErrorProps): ReactNode => (
  <div className="container mx-auto my-4 px-4">
    <ApiErrorAlert apiError={message} />
  </div>
);

export default AdminAppConfigurationError;
