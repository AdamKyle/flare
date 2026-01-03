import { Screens } from 'configuration/screen-manager/screen-manager-constants';
import {
  useBindScreen,
  useScreenNavigation,
} from 'configuration/screen-manager/screen-manager-kit';
import { ScreenPropsOf } from 'configuration/screen-manager/screen-manager-props';
import { useRef } from 'react';

import { useStatDetailsVisibility } from '../../components/character-sheet/hooks/use-stat-details-visibility';

const BindCharacterInventory = () => {
  const { pop } = useScreenNavigation();
  const { closeStatDetails, showStatDetails, statType } =
    useStatDetailsVisibility();

  const activeRef = useRef(false);

  useBindScreen({
    when: showStatDetails,
    to: Screens.CHARACTER_STAT_DETAILS,
    props: (): ScreenPropsOf<typeof Screens.CHARACTER_STAT_DETAILS> => ({
      close_stat_type: () => {
        if (activeRef.current) {
          pop();
        }
        closeStatDetails();
        activeRef.current = false;
      },
      stat_type: statType,
    }),
    mode: 'push',
    dedupeKey: 'character-inventory',
  });

  return null;
};

export default BindCharacterInventory;
