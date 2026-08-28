import clsx from 'clsx';
import React, { ReactNode } from 'react';

import { AdminPageWidth } from '../enums/admin-page-width';
import { ADMIN_PAGE_WIDTH_STYLES } from '../styles/admin-page-width-styles';
import AdminPageProps from '../types/admin-page-props';

const AdminPage = ({
  title,
  width,
  header_actions: headerActions,
  children,
}: AdminPageProps): ReactNode => {
  const isWorkspace = width === AdminPageWidth.Workspace;

  const renderHeaderActions = (): ReactNode => {
    if (!headerActions) {
      return null;
    }

    return (
      <div className="flex flex-wrap items-center gap-2">{headerActions}</div>
    );
  };

  return (
    <div
      className={clsx(
        'flex w-full min-w-0 flex-1 flex-col py-6 sm:px-4',
        !isWorkspace && 'md:justify-center'
      )}
    >
      <div
        className={clsx(
          'mx-auto w-full min-w-0',
          ADMIN_PAGE_WIDTH_STYLES[width]
        )}
      >
        <div className="mb-6 flex flex-wrap items-center justify-between gap-3 px-2 sm:px-0">
          <h1 className="text-glacier-900 dark:text-glacier-100 text-xl font-semibold">
            {title}
          </h1>
          {renderHeaderActions()}
        </div>
        {children}
      </div>
    </div>
  );
};

export default AdminPage;
