import { Screens } from 'configuration/screen-manager/screen-manager-constants';
import {
  useBindScreen,
  useScreenNavigation,
} from 'configuration/screen-manager/screen-manager-kit';
import { ScreenPropsOf } from 'configuration/screen-manager/screen-manager-props';
import { useRef } from 'react';

import { useManageCharacterInventoryVisibility } from '../components/character-sheet/hooks/use-manage-character-inventory-visibility';

const BindCharacterInventory = () => {
  const { pop } = useScreenNavigation();
  const { closeInventory, showInventory } =
    useManageCharacterInventoryVisibility();

  const activeRef = useRef(false);

  useBindScreen({
    when: showInventory,
    to: Screens.CHARACTER_INVENTORY,
    props: (): ScreenPropsOf<typeof Screens.CHARACTER_INVENTORY> => ({
      close_inventory: () => {
        if (activeRef.current) {
          pop();
        }
        closeInventory();
        activeRef.current = false;
      },
    }),
    mode: 'push',
    dedupeKey: 'character-inventory',
  });

  return null;
};

export default BindCharacterInventory;
