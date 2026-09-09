import React, { ReactNode } from 'react';

import FactualLinkProps from '../types/partials/factual-link-props';

const FactualLink = ({
  id,
  label,
  on_click: onClick,
}: FactualLinkProps): ReactNode => {
  if (!onClick) {
    return <span className="text-gray-800 dark:text-gray-200">{label}</span>;
  }

  return (
    <button
      type="button"
      onClick={() => onClick(id)}
      className="focus-visible:ring-brand-600 dark:focus-visible:ring-brand-400 rounded-sm font-medium text-gray-900 underline decoration-gray-400 underline-offset-2 hover:text-gray-600 focus:outline-none focus-visible:ring-2 dark:text-gray-100 dark:decoration-gray-500 dark:hover:text-gray-300"
    >
      {label}
    </button>
  );
};

export default FactualLink;
