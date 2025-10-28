import { useRef } from 'react';

import { Screens } from '../../config/screen-manager/screen-manager-constants';
import {
  useBindScreen,
  useScreenNavigation,
} from '../../config/screen-manager/screen-manager-kit';
import { ScreenPropsOf } from '../../config/screen-manager/screen-manager-props';
import { useManageShopSectionVisibility } from '../components/shop/hooks/use-manage-shop-section-visibility';

const BindShop = () => {
  const { pop } = useScreenNavigation();
  const { closeShopSection, showShopSection } =
    useManageShopSectionVisibility();

  const activeRef = useRef(false);

  useBindScreen({
    when: showShopSection,
    to: Screens.SHOP,
    props: (): ScreenPropsOf<typeof Screens.SHOP> => ({
      close_shop: () => {
        if (activeRef.current) {
          pop();
        }
        closeShopSection();
        activeRef.current = false;
      },
    }),
    mode: 'push',
    dedupeKey: 'character-sheet',
  });

  return null;
};

export default BindShop;
