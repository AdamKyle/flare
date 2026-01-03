import { Screens } from 'configuration/screen-manager/screen-manager-constants';
import {
  useBindScreen,
  useScreenNavigation,
} from 'configuration/screen-manager/screen-manager-kit';
import { ScreenPropsOf } from 'configuration/screen-manager/screen-manager-props';
import { useRef } from 'react';

import { useManageShopSectionVisibility } from '../../components/shop/hooks/use-manage-shop-section-visibility';

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
    dedupeKey: 'shop',
  });

  return null;
};

export default BindShop;
