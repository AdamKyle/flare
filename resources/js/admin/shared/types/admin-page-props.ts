import { ReactNode } from 'react';

import { AdminPageWidth } from '../enums/admin-page-width';

export default interface AdminPageProps {
  title: string;
  width: AdminPageWidth;
  header_actions?: ReactNode;
  children: ReactNode;
}
