import React, { ReactNode } from 'react';

import ClassPrerequisiteCardProps from '../types/class-prerequisite-card-props';

const baseStyles =
  'w-full flex items-center justify-between gap-3 rounded-lg border-2 p-3 text-left transition-colors';

const themeStyles =
  'border-danube-400 dark:border-danube-500 bg-danube-100 dark:bg-danube-900';

const interactiveStyles =
  'hover:bg-danube-200 dark:hover:bg-danube-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-danube-500 dark:focus-visible:ring-danube-300 cursor-pointer';

const ClassPrerequisiteCard = ({
  id,
  name,
  required_level: requiredLevel,
  current_level: currentLevel,
  is_met: isMet,
  on_click: onClick,
}: ClassPrerequisiteCardProps): ReactNode => {
  const renderContent = (): ReactNode => (
    <div className="flex min-w-0 flex-1 flex-col gap-0.5">
      <span className="text-danube-900 dark:text-danube-100 truncate font-semibold">
        {name}
      </span>
      <span className="text-danube-700 dark:text-danube-200 text-xs">
        Requires Level {requiredLevel}
        {typeof currentLevel === 'number' && ` · Current Level ${currentLevel}`}
        {typeof isMet === 'boolean' &&
          (isMet ? ' · Requirement met' : ' · Requirement not met')}
      </span>
    </div>
  );

  if (onClick) {
    return (
      <button
        type="button"
        onClick={() => onClick(id)}
        aria-label={`View ${name} Class`}
        className={`${baseStyles} ${themeStyles} ${interactiveStyles}`}
      >
        {renderContent()}
      </button>
    );
  }

  return (
    <div aria-label={name} className={`${baseStyles} ${themeStyles}`}>
      {renderContent()}
    </div>
  );
};

export default ClassPrerequisiteCard;
