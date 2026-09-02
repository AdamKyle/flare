import React, { ReactNode } from 'react';

import FactualLink from '../../quest-item/partials/factual-link';
import QuestDetailProps from '../types/quest-detail-props';

const fieldLabelClassName =
  'text-glacier-600 dark:text-glacier-400 text-xs font-semibold tracking-wide uppercase';

const QuestDependenciesSection = ({
  quest,
  navigation,
}: QuestDetailProps): ReactNode => {
  const { structure } = quest;

  const hasRows =
    Boolean(structure.parent_quest) ||
    structure.child_quests.length > 0 ||
    Boolean(structure.required_quest) ||
    structure.required_quest_chain.length > 0;

  if (!hasRows) {
    return null;
  }

  return (
    <div className="flex flex-col gap-4">
      <h3 className="text-glacier-900 dark:text-glacier-100 mb-1 text-sm font-semibold">
        Structure &amp; Dependencies
      </h3>

      {structure.parent_quest && (
        <div>
          <p className={fieldLabelClassName}>Parent Quest</p>
          <div className="text-glacier-800 dark:text-glacier-200 mt-1 text-sm">
            <FactualLink
              id={structure.parent_quest.id}
              label={structure.parent_quest.name}
              on_click={navigation?.on_open_quest}
            />
          </div>
        </div>
      )}

      {structure.child_quests.length > 0 && (
        <div>
          <p className={fieldLabelClassName}>Child Quests</p>
          <ul className="text-glacier-800 dark:text-glacier-200 mt-1 space-y-1 text-sm">
            {structure.child_quests.map((child) => (
              <li key={child.id}>
                <FactualLink
                  id={child.id}
                  label={child.name}
                  on_click={navigation?.on_open_quest}
                />
              </li>
            ))}
          </ul>
        </div>
      )}

      {structure.required_quest && (
        <div>
          <p className={fieldLabelClassName}>Required Quest</p>
          <div className="text-glacier-800 dark:text-glacier-200 mt-1 text-sm">
            <FactualLink
              id={structure.required_quest.id}
              label={structure.required_quest.name}
              on_click={navigation?.on_open_quest}
            />
          </div>
        </div>
      )}

      {structure.required_quest_chain.length > 0 && (
        <div>
          <p className={fieldLabelClassName}>Required Quest Chain</p>
          <ol className="text-glacier-800 dark:text-glacier-200 mt-1 space-y-1 text-sm">
            {structure.required_quest_chain.map((required, index) => (
              <li key={required.id} className="flex items-baseline gap-2">
                <span className="text-glacier-500 dark:text-glacier-500 text-xs">
                  {index + 1}.
                </span>
                <FactualLink
                  id={required.id}
                  label={required.name}
                  on_click={navigation?.on_open_quest}
                />
              </li>
            ))}
          </ol>
        </div>
      )}
    </div>
  );
};

export default QuestDependenciesSection;
