import { Screens } from 'configuration/screen-manager/screen-manager-constants';
import {
  useBindScreen,
  useScreenNavigation,
} from 'configuration/screen-manager/screen-manager-kit';
import { ScreenPropsOf } from 'configuration/screen-manager/screen-manager-props';
import { useRef } from 'react';

import { useManageMarketVisibility } from '../../components/actions/partials/floating-cards/map-section/hooks/use-manage-market-visibility';

const BindMarket = () => {
  const { pop } = useScreenNavigation();
  const { showMarket, closeMarket } = useManageMarketVisibility();

  const activeRef = useRef(false);

  useBindScreen({
    when: showMarket,
    to: Screens.MARKET,
    props: (): ScreenPropsOf<typeof Screens.MARKET> => ({
      close_market: () => {
        if (activeRef.current) {
          pop();
        }
        closeMarket();
        activeRef.current = false;
      },
    }),
    mode: 'push',
    dedupeKey: 'market',
  });

  return null;
};

export default BindMarket;
