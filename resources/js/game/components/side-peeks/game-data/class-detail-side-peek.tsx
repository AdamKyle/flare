import React, { ReactNode } from 'react';

import ClassDetailSidePeekProps from './types/class-detail-side-peek-props';
import ClassDetail from '../../../reusable-components/class/components/class-detail';

const ClassDetailSidePeek = ({
  class_detail: classDetail,
}: ClassDetailSidePeekProps): ReactNode => {
  return (
    <div className="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5">
      <ClassDetail game_class={classDetail} />
    </div>
  );
};

export default ClassDetailSidePeek;
