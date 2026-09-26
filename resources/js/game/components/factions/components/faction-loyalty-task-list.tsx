import React, { ReactNode } from 'react';

import FactionLoyaltyTaskRow from './faction-loyalty-task-row';
import FactionLoyaltyTaskListProps from './types/faction-loyalty-task-list-props';
import FactionLoyaltyTaskDefinition from '../api/definitions/faction-loyalty-task-definition';

const FactionLoyaltyTaskList = ({
  tasks,
}: FactionLoyaltyTaskListProps): ReactNode => {
  const renderTask = (task: FactionLoyaltyTaskDefinition, index: number) => (
    <FactionLoyaltyTaskRow
      key={`${task.type}-${task.item_id ?? task.monster_id ?? index}`}
      task={task}
    />
  );

  if (tasks.length === 0) {
    return (
      <p className="text-sm text-gray-700 dark:text-gray-300">
        This NPC has no tasks right now.
      </p>
    );
  }

  return <ul className="space-y-3">{tasks.map(renderTask)}</ul>;
};

export default FactionLoyaltyTaskList;
