import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import ClassDetailBodyProps from './types/class-detail-body-props';
import { usePlayerClassDetail } from '../../../../reusable-components/class/api/hooks/use-player-class-detail';
import ClassDetail from '../../../../reusable-components/class/components/class-detail';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const ClassDetailBody = ({
  class_id: classId,
  on_open_class: onOpenClass,
}: ClassDetailBodyProps): ReactNode => {
  const {
    game_class: gameClass,
    loading,
    error,
  } = usePlayerClassDetail(classId);

  if (loading) {
    return <InfiniteLoader />;
  }

  if (error || !gameClass) {
    return (
      <ApiErrorAlert
        apiError={error?.message ?? 'Unable to load this Class.'}
      />
    );
  }

  return (
    <div className="flex flex-col gap-4">
      <h2 className="text-xl font-bold text-gray-900 dark:text-gray-100">
        {gameClass.name}
      </h2>
      <ClassDetail
        game_class={gameClass}
        on_open_class={onOpenClass}
        single_column
      />
    </div>
  );
};

export default ClassDetailBody;
