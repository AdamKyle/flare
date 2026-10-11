import React, { ReactNode, useState } from 'react';

import ClassDetailBody from './components/class-detail-body';
import ClassDetailStack from './components/class-detail-stack';
import ClassDetailSidePeekProps from './types/class-detail-side-peek-props';

const ClassDetailSidePeek = ({
  class_id: classId,
}: ClassDetailSidePeekProps): ReactNode => {
  const [nestedClassId, setNestedClassId] = useState<number | null>(null);

  const renderNestedClass = (): ReactNode => {
    if (nestedClassId === null) {
      return null;
    }

    return (
      <ClassDetailStack
        class_id={nestedClassId}
        on_close={() => setNestedClassId(null)}
      />
    );
  };

  return (
    <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
      <div className="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5">
        <ClassDetailBody class_id={classId} on_open_class={setNestedClassId} />
      </div>
      {renderNestedClass()}
    </div>
  );
};

export default ClassDetailSidePeek;
