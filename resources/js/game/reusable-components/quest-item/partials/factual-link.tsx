import React, { ReactNode } from 'react';

import FactualLinkProps from '../types/partials/factual-link-props';

/**
 * Render a factual relationship identity as an accessible interactive
 * control when its navigation callback is supplied, otherwise render it as
 * plain factual text. Never checks Admin permission, imports Admin APIs, or
 * mutates data; the caller decides navigation behavior entirely through the
 * optional `on_click` callback.
 */
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
      className="text-danube-700 hover:text-danube-600 focus-visible:ring-danube-400 dark:text-danube-200 dark:hover:text-danube-100 decoration-danube-400 dark:decoration-danube-500 rounded-sm font-medium underline underline-offset-2 focus:outline-none focus-visible:ring-2"
    >
      {label}
    </button>
  );
};

export default FactualLink;
