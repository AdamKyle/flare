import { SidePeekComponentRegistrationEnum } from '../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import { SkillImportCopy } from '../enums/skill-import-copy';

export const useOpenSkillImportSidePeek = (
  onImported: () => void
): (() => void) => {
  const emitter = useSidePeekEmitter();

  return () =>
    emitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_SKILL_IMPORT,
      {
        is_open: true,
        title: SkillImportCopy.Title,
        allow_clicking_outside: true,
        on_imported: onImported,
      }
    );
};
