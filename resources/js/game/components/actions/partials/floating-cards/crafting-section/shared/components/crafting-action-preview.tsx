import clsx from 'clsx';
import React, { ReactNode, useId } from 'react';

import { STATUS_BORDER_STYLES } from './styles/crafting-action-preview-status-styles';
import CraftingActionPreviewProps from './types/crafting-action-preview-props';

const CraftingActionPreview = ({
  title,
  description,
  status = 'default',
  children,
}: CraftingActionPreviewProps): ReactNode => {
  const headingId = useId();

  const renderDescription = () => {
    if (!description) {
      return null;
    }

    return (
      <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
        {description}
      </p>
    );
  };

  return (
    <section
      aria-labelledby={headingId}
      className={clsx(
        'space-y-2 rounded-md border p-3',
        STATUS_BORDER_STYLES[status]
      )}
    >
      <h3
        id={headingId}
        className="text-sm font-semibold text-gray-900 dark:text-gray-100"
      >
        {title}
      </h3>
      {renderDescription()}
      {children}
    </section>
  );
};

export default CraftingActionPreview;
