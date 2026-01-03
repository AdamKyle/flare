import { Screens } from 'configuration/screen-manager/screen-manager-constants';
import {
  useBindScreen,
  useScreenNavigation,
} from 'configuration/screen-manager/screen-manager-kit';
import { ScreenPropsOf } from 'configuration/screen-manager/screen-manager-props';
import { useRef } from 'react';

import { useManageDonationsVisibility } from '../../components/donations/hooks/use-manage-donations-visibility';

const BindDonationsSection = () => {
  const { pop } = useScreenNavigation();
  const { closeDonationScreen, showDonations } = useManageDonationsVisibility();

  const activeRef = useRef(false);

  useBindScreen({
    when: showDonations,
    to: Screens.DONATIONS,
    props: (): ScreenPropsOf<typeof Screens.DONATIONS> => ({
      on_close: () => {
        if (activeRef.current) {
          pop();
        }
        closeDonationScreen();
        activeRef.current = false;
      },
    }),
    mode: 'push',
    dedupeKey: 'shop',
  });

  return null;
};

export default BindDonationsSection;
