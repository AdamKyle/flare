import UseOpenClassDetailDefinition from './definitions/use-open-class-detail-definition';
import { SidePeekComponentRegistrationEnum } from '../../base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../base/event-types/side-peek';
import { useSidePeekEmitter } from '../../base/hooks/use-side-peek-emitter';

export const useOpenClassDetail = (): UseOpenClassDetailDefinition => {
  const sidePeekEmitter = useSidePeekEmitter();

  const openClassDetail = (classId: number, className: string): void => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.CLASS_DETAIL,
      {
        is_open: true,
        title: className,
        allow_clicking_outside: true,
        class_id: classId,
      }
    );
  };

  return { openClassDetail };
};
