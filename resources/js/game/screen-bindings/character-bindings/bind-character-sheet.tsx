import { Screens } from 'configuration/screen-manager/screen-manager-constants';
import {
  useBindScreen,
  useScreenNavigation,
} from 'configuration/screen-manager/screen-manager-kit';
import { ScreenPropsOf } from 'configuration/screen-manager/screen-manager-props';
import { useRef } from 'react';

import { useCharacterSheetVisibility } from '../../components/hooks/use-character-sheet-visibility';
import { useManageCharacterSheetVisibility } from '../../components/hooks/use-manage-character-sheet-visibility';

const BindCharacterSheet = () => {
  const { pop } = useScreenNavigation();
  const { showCharacterSheet } = useCharacterSheetVisibility();
  const { closeCharacterSheet } = useManageCharacterSheetVisibility();
  const activeRef = useRef(false);

  useBindScreen({
    when: showCharacterSheet,
    to: Screens.CHARACTER_SHEET,
    props: (): ScreenPropsOf<typeof Screens.CHARACTER_SHEET> => ({
      manageCharacterSheetVisibility: () => {
        if (activeRef.current) {
          pop();
        }
        closeCharacterSheet();
        activeRef.current = false;
      },
    }),
    mode: 'push',
    dedupeKey: 'character-sheet',
  });

  return null;
};

export default BindCharacterSheet;
