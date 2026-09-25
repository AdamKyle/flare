import React, { ReactNode } from 'react';

import AreaGemContext from './area-gem-context';
import GemContextDetailProps from '../types/gem-context-detail-props';

const GemContextEffectsDetail = ({
  context,
}: GemContextDetailProps): ReactNode => {
  const renderRules = (): ReactNode => {
    if (context.rules.length === 0) {
      return null;
    }

    return (
      <div>
        <h3 className="mb-1 text-sm font-semibold text-gray-800 dark:text-gray-200">
          Rules Applied
        </h3>
        <ul className="list-disc space-y-1 pl-5 text-sm text-gray-700 dark:text-gray-300">
          {context.rules.map((rule) => (
            <li key={rule}>{rule}</li>
          ))}
        </ul>
      </div>
    );
  };

  return (
    <div className="flex h-full flex-col gap-3 overflow-y-auto px-4 py-4 sm:px-5">
      {renderRules()}
      <AreaGemContext context={context} />
    </div>
  );
};

export default GemContextEffectsDetail;
