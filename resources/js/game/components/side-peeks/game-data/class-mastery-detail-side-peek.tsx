import React, { ReactNode } from 'react';

import ClassMasteryDetailSidePeekProps from './types/class-mastery-detail-side-peek-props';
import ClassMasteryDetail from '../../../reusable-components/class-mastery/components/class-mastery-detail';

const ClassMasteryDetailSidePeek = ({
  class_mastery: classMastery,
}: ClassMasteryDetailSidePeekProps): ReactNode => {
  return (
    <div className="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5">
      <ClassMasteryDetail class_mastery={classMastery} />
    </div>
  );
};

export default ClassMasteryDetailSidePeek;
