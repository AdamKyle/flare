import React, { ReactNode, useId } from 'react';

import CraftingActionPreviewProps from './types/crafting-action-preview-props';

const CraftingActionPreview = ({
  title,
  description,
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
      className="space-y-2 rounded-md border border-gray-400 p-3 dark:border-gray-600"
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
