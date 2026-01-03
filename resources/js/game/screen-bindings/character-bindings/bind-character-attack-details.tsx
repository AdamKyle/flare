import { Screens } from 'configuration/screen-manager/screen-manager-constants';
import {
  useBindScreen,
  useScreenNavigation,
} from 'configuration/screen-manager/screen-manager-kit';
import { ScreenPropsOf } from 'configuration/screen-manager/screen-manager-props';
import { useRef } from 'react';

import { useAttackDetailsVisibility } from '../../components/character-sheet/hooks/use-attack-details-visibility';

const BindCharacterInventory = () => {
  const { pop } = useScreenNavigation();
  const { showAttackType, closeAttackDetails, attackType } =
    useAttackDetailsVisibility();

  const activeRef = useRef(false);

  useBindScreen({
    when: showAttackType,
    to: Screens.CHARACTER_ATTACK_DETAILS,
    props: (): ScreenPropsOf<typeof Screens.CHARACTER_ATTACK_DETAILS> => ({
      close_attack_details: () => {
        if (activeRef.current) {
          pop();
        }
        closeAttackDetails();
        activeRef.current = false;
      },
      attack_type: attackType,
    }),
    mode: 'push',
    dedupeKey: 'character-inventory',
  });

  return null;
};

export default BindCharacterInventory;
