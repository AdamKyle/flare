import React, { ReactNode, useId } from 'react';

import CraftingActionLayoutProps from './types/crafting-action-layout-props';

const CraftingActionLayout = ({
  title,
  status,
  progress,
  form,
  preview,
  action,
  help_href,
  help_label,
}: CraftingActionLayoutProps): ReactNode => {
  const headingId = useId();

  const renderHelpLink = () => {
    if (!help_href || !help_label) {
      return null;
    }

    return (
      <a
        href={help_href}
        target="_blank"
        rel="noopener noreferrer"
        className="text-danube-700 focus:ring-danube-500 dark:text-danube-300 font-semibold underline focus:ring-2 focus:outline-none"
      >
        {help_label} (opens in a new tab)
      </a>
    );
  };

  return (
    <section aria-labelledby={headingId} className="space-y-4">
      <h2
        id={headingId}
        className="text-xl font-semibold text-gray-900 dark:text-gray-100"
      >
        {title}
      </h2>
      {status}
      {progress}
      {form}
      {preview}
      {action}
      {renderHelpLink()}
    </section>
  );
};

export default CraftingActionLayout;
