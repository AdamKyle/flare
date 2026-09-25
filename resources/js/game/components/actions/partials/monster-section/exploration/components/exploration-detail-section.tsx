import React, { ReactNode } from 'react';

import ExplorationDetailSectionProps from '../types/exploration-detail-section-props';

const ExplorationDetailSection = ({
  title,
  children,
}: ExplorationDetailSectionProps): ReactNode => {
  return (
    <section className="space-y-2 rounded-md border border-gray-300 p-3 dark:border-gray-700">
      <h4 className="text-sm font-semibold text-gray-900 dark:text-gray-100">
        {title}
      </h4>
      {children}
    </section>
  );
};

export default ExplorationDetailSection;
