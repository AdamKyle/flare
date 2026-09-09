import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import { SidePeekComponentRegistrationEnum } from '../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import { LocationGemApiMessages } from '../api/enums/location-gem-api-messages';
import { useLocationGemDetail } from '../api/hooks/use-location-gem-detail';
import { useRollLocationGem } from '../api/hooks/use-roll-location-gem';
import LocationGemDetailBody from '../components/location-gem-detail-body';
import LocationGemRolledProfilesTab from '../components/location-gem-rolled-profiles-tab';
import { LocationGemScreens } from '../screen-manager/location-gem-screen-constants';
import { useLocationGemScreenNavigation } from '../screen-manager/location-gem-screen-kit';
import { LocationGemShowScreenProps } from '../screen-manager/location-gem-screen-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Card from 'ui/cards/card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import PillTabs from 'ui/tabs/pill-tabs';

const TAB_CSS = 'min-w-0 flex-1 px-4 sm:min-w-56 sm:flex-none';

const CardWrappedLocationGemDetailBody = (
  props: React.ComponentProps<typeof LocationGemDetailBody>
): ReactNode => (
  <Card>
    <LocationGemDetailBody {...props} />
  </Card>
);

const CardWrappedLocationGemRolledProfilesTab = (
  props: React.ComponentProps<typeof LocationGemRolledProfilesTab>
): ReactNode => (
  <Card>
    <LocationGemRolledProfilesTab {...props} />
  </Card>
);

const LocationGemShowScreen = ({
  location_gem_id: locationGemId,
}: LocationGemShowScreenProps): ReactNode => {
  const navigation = useLocationGemScreenNavigation();
  const sidePeekEmitter = useSidePeekEmitter();
  const {
    location_gem: locationGem,
    loading,
    error,
    refresh,
  } = useLocationGemDetail(locationGemId);
  const { rolling, error: rollError, roll } = useRollLocationGem();
  const [rollAnnouncement, setRollAnnouncement] = useState('');

  const handleBack = (): void => {
    navigation.pop();
  };

  const handleEdit = (): void => {
    navigation.navigateTo(LocationGemScreens.FORM, {
      location_gem_id: locationGemId,
    });
  };

  const handleOpenMap = (id: number): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_DETAIL,
      {
        is_open: true,
        title: 'Game Map Details',
        allow_clicking_outside: true,
        game_map_id: id,
      }
    );
  };

  const handleOpenLocation = (id: number): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_LOCATION_DETAIL,
      {
        is_open: true,
        title: 'Location Details',
        allow_clicking_outside: true,
        location_id: id,
      }
    );
  };

  const handleRoll = async (): Promise<void> => {
    const rolled = await roll(locationGemId);

    if (rolled) {
      setRollAnnouncement(`Rolled ${rolled.rolled_gem?.name ?? 'a new Gem'}.`);
      refresh();
    }
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !locationGem) {
      return (
        <ApiErrorAlert
          apiError={error?.message ?? LocationGemApiMessages.Load}
        />
      );
    }

    const tabs = [
      {
        label: 'Profile',
        component: CardWrappedLocationGemDetailBody,
        props: {
          location_gem: locationGem,
          navigation: {
            on_open_map: handleOpenMap,
            on_open_location: handleOpenLocation,
          },
        },
      },
      {
        label: 'Rolled Profiles',
        component: CardWrappedLocationGemRolledProfilesTab,
        props: { location_gem_id: locationGemId },
      },
    ] as const;

    return (
      <div className="flex flex-col gap-6">
        <div className="flex flex-wrap justify-start gap-2 py-2">
          <Button
            label="Edit Location Gem"
            variant={ButtonVariant.PRIMARY}
            additional_css="text-sm px-3 py-1.5"
            on_click={handleEdit}
          />
          <Button
            label={rolling ? 'Rolling…' : 'Roll Gem'}
            variant={ButtonVariant.SUCCESS}
            additional_css="text-sm px-3 py-1.5"
            on_click={() => void handleRoll()}
            disabled={rolling}
          />
        </div>

        {rollError && <ApiErrorAlert apiError={rollError.message} />}

        <PillTabs
          tabs={tabs}
          ariaLabel="Location Gem detail"
          additional_tab_css={TAB_CSS}
        />

        <p className="sr-only" role="status" aria-live="polite">
          {rollAnnouncement}
        </p>
      </div>
    );
  };

  return (
    <AdminPage
      title={locationGem?.name ?? 'Location Gem'}
      width={AdminPageWidth.Detail}
      header_actions={<AdminBackButton on_click={handleBack} />}
    >
      {renderContent()}
    </AdminPage>
  );
};

export default LocationGemShowScreen;
