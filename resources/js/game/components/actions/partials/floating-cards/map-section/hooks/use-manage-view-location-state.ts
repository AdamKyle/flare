import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import LocationInfo from './types/location-info';
import { MapActions } from '../event-types/map-actions';
import UseManageViewLocationDefinition from './definitions/use-manage-view-location-definition';

export const useManageViewLocationState =
  (): UseManageViewLocationDefinition => {
    const eventSystem = useEventSystem();

    const [isViewLocationEnabled, setIsViewLocationEnabled] = useState(false);
    const [locationData, setLocationData] = useState<LocationInfo | null>(null);

    const manageViewLocationStateEmitter =
      eventSystem.fetchOrCreateEventEmitter<{
        [key: string]: [boolean, LocationInfo | null];
      }>(MapActions.ALLOW_VIEW_LOCATION);

    useEffect(() => {
      const manageButtonState = (
        isEnabled: boolean,
        locationInfo: LocationInfo | null
      ) => {
        setIsViewLocationEnabled(isEnabled);
        setLocationData(locationInfo);
      };

      manageViewLocationStateEmitter.on(
        MapActions.ALLOW_VIEW_LOCATION,
        manageButtonState
      );

      return () => {
        manageViewLocationStateEmitter.off(
          MapActions.ALLOW_VIEW_LOCATION,
          manageButtonState
        );
      };
      // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const canViewLocationData = (
      isEnabled: boolean,
      locationInfo: LocationInfo | null
    ) => {
      manageViewLocationStateEmitter.emit(
        MapActions.ALLOW_VIEW_LOCATION,
        isEnabled,
        locationInfo
      );
    };

    return {
      isViewLocationEnabled,
      locationData,
      canViewLocationData,
    };
  };
