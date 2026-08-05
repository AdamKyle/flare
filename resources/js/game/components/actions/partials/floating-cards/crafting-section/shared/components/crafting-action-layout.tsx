import React, { ReactNode } from 'react';

import CraftingActionLayoutProps from './types/crafting-action-layout-props';

const CraftingActionLayout = ({
  heading,
  status,
  progress,
  form,
  preview,
  result,
  action,
  help_link,
}: CraftingActionLayoutProps): ReactNode => {
  return (
    <div className="space-y-4">
      {heading}
      {status}
      {progress}
      {form}
      {preview}
      {result}
      {action}
      {help_link}
    </div>
  );
};

export default CraftingActionLayout;
