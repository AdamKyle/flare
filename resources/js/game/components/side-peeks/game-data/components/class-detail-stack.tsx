import React, { ReactNode, useState } from 'react';

import ClassDetailBody from './class-detail-body';
import ClassDetailStackProps from './types/class-detail-stack-props';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';

const ClassDetailStack = ({
  class_id: classId,
  on_close: onClose,
}: ClassDetailStackProps): ReactNode => {
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
    <StackedCard
      on_close={onClose}
      aria_label="Class Details"
      content_mode={StackedCardContentMode.FULL_BLEED}
    >
      <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
        <div className="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5">
          <ClassDetailBody
            class_id={classId}
            on_open_class={setNestedClassId}
          />
        </div>
        {renderNestedClass()}
      </div>
    </StackedCard>
  );
};

export default ClassDetailStack;
