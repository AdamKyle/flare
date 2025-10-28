import { useRef } from 'react';

import { Screens } from '../../config/screen-manager/screen-manager-constants';
import {
  useBindScreen,
  useScreenNavigation,
} from '../../config/screen-manager/screen-manager-kit';
import { ScreenPropsOf } from '../../config/screen-manager/screen-manager-props';
import { useManageGoblinShopVisibility } from '../components/goblin-shop/hooks/use-manage-goblin-shop-visibility';

const BindGoblinShop = () => {
  const { pop } = useScreenNavigation();
  const { closeGoblinShop, showGoblinShop } = useManageGoblinShopVisibility();

  const activeRef = useRef(false);

  useBindScreen({
    when: showGoblinShop,
    to: Screens.GOBLIN_SHOP,
    props: (): ScreenPropsOf<typeof Screens.GOBLIN_SHOP> => ({
      on_close: () => {
        if (activeRef.current) {
          pop();
        }
        closeGoblinShop();
        activeRef.current = false;
      },
    }),
    mode: 'push',
    dedupeKey: 'character-sheet',
  });

  return null;
};

export default BindGoblinShop;
