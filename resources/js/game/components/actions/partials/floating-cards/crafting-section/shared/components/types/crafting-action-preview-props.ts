import { ReactNode } from 'react';

export type CraftingActionPreviewStatus = 'default' | 'success' | 'danger';

export default interface CraftingActionPreviewProps {
  title: string;
  description?: string;
  status?: CraftingActionPreviewStatus;
  children: ReactNode;
}
