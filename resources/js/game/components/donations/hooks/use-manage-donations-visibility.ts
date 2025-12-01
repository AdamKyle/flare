import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import { DonationsEventTypes } from '../event-types/donation-event-types';
import UseManageDonationsVisibilityDefinition from './definitions/use-manage-donations-visibility-definition';

export const useManageDonationsVisibility =
  (): UseManageDonationsVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const [showDonations, setShowDonations] = useState(false);

    const manageInventoryEmitter = eventSystem.fetchOrCreateEventEmitter<{
      [key: string]: boolean;
    }>(DonationsEventTypes.OPEN_DONATIONS);

    useEffect(() => {
      const updateVisibility = (visible: boolean) => {
        setShowDonations(visible);
      };

      manageInventoryEmitter.on(
        DonationsEventTypes.OPEN_DONATIONS,
        updateVisibility
      );

      return () => {
        manageInventoryEmitter.off(
          DonationsEventTypes.OPEN_DONATIONS,
          updateVisibility
        );
      };
    }, [manageInventoryEmitter]);

    const openDonationScreen = () => {
      manageInventoryEmitter.emit(DonationsEventTypes.OPEN_DONATIONS, true);
    };

    const closeDonationScreen = () => {
      manageInventoryEmitter.emit(DonationsEventTypes.OPEN_DONATIONS, false);
    };

    return {
      showDonations,
      openDonationScreen,
      closeDonationScreen,
    };
  };
