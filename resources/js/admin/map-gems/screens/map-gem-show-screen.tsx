import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import { SidePeekComponentRegistrationEnum } from '../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import { MapGemApiMessages } from '../api/enums/map-gem-api-messages';
import { useMapGemDetail } from '../api/hooks/use-map-gem-detail';
import { useRollMapGem } from '../api/hooks/use-roll-map-gem';
import MapGemDetailBody from '../components/map-gem-detail-body';
import MapGemRolledProfilesTab from '../components/map-gem-rolled-profiles-tab';
import { MapGemScreens } from '../screen-manager/map-gem-screen-constants';
import { useMapGemScreenNavigation } from '../screen-manager/map-gem-screen-kit';
import { MapGemShowScreenProps } from '../screen-manager/map-gem-screen-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Card from 'ui/cards/card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import PillTabs from 'ui/tabs/pill-tabs';

const TAB_CSS = 'min-w-0 flex-1 px-4 sm:min-w-56 sm:flex-none';

const CardWrappedMapGemDetailBody = (
  props: React.ComponentProps<typeof MapGemDetailBody>
): ReactNode => (
  <Card>
    <MapGemDetailBody {...props} />
  </Card>
);

const CardWrappedMapGemRolledProfilesTab = (
  props: React.ComponentProps<typeof MapGemRolledProfilesTab>
): ReactNode => (
  <Card>
    <MapGemRolledProfilesTab {...props} />
  </Card>
);

const MapGemShowScreen = ({
  map_gem_id: mapGemId,
}: MapGemShowScreenProps): ReactNode => {
  const navigation = useMapGemScreenNavigation();
  const sidePeekEmitter = useSidePeekEmitter();
  const {
    map_gem: mapGem,
    loading,
    error,
    refresh,
  } = useMapGemDetail(mapGemId);
  const { rolling, error: rollError, roll } = useRollMapGem();
  const [rollAnnouncement, setRollAnnouncement] = useState('');

  const handleBack = (): void => {
    navigation.pop();
  };

  const handleEdit = (): void => {
    navigation.navigateTo(MapGemScreens.FORM, { map_gem_id: mapGemId });
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

  const handleRoll = async (): Promise<void> => {
    const rolled = await roll(mapGemId);

    if (rolled) {
      setRollAnnouncement(`Rolled ${rolled.rolled_gem?.name ?? 'a new Gem'}.`);
      refresh();
    }
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !mapGem) {
      return (
        <ApiErrorAlert apiError={error?.message ?? MapGemApiMessages.Load} />
      );
    }

    const tabs = [
      {
        label: 'Profile',
        component: CardWrappedMapGemDetailBody,
        props: {
          map_gem: mapGem,
          navigation: { on_open_map: handleOpenMap },
        },
      },
      {
        label: 'Rolled Profiles',
        component: CardWrappedMapGemRolledProfilesTab,
        props: { map_gem_id: mapGemId },
      },
    ] as const;

    return (
      <div className="flex flex-col gap-6">
        <div className="flex flex-wrap justify-start gap-2 py-2">
          <Button
            label="Edit Map Gem"
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
          ariaLabel="Map Gem detail"
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
      title={mapGem?.name ?? 'Map Gem'}
      width={AdminPageWidth.Detail}
      header_actions={<AdminBackButton on_click={handleBack} />}
    >
      {renderContent()}
    </AdminPage>
  );
};

export default MapGemShowScreen;
