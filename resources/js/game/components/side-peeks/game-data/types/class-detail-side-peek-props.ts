import ClassDetailDefinition from '../../../../reusable-components/class/types/class-detail-definition';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface ClassDetailSidePeekProps extends SidePeekProps {
  class_detail: ClassDetailDefinition;
}
