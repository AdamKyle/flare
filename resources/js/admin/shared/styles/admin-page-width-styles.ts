import { AdminPageWidth } from '../enums/admin-page-width';

export const ADMIN_PAGE_WIDTH_STYLES: Record<AdminPageWidth, string> = {
  [AdminPageWidth.Standard]: 'w-full md:w-2/3',
  [AdminPageWidth.Detail]: 'w-full md:w-2/3',
  [AdminPageWidth.Workspace]: 'w-full',
};
