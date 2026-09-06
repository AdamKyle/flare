import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import { MapGemApiMessages } from '../api/enums/map-gem-api-messages';
import { useActivateMapGemRoll } from '../api/hooks/use-activate-map-gem-roll';
import { useMapGemDetail } from '../api/hooks/use-map-gem-detail';
import { useRollMapGem } from '../api/hooks/use-roll-map-gem';
import MapGemDetailBody from '../components/map-gem-detail-body';
import { MapGemScreens } from '../screen-manager/map-gem-screen-constants';
import { useMapGemScreenNavigation } from '../screen-manager/map-gem-screen-kit';
import { MapGemShowScreenProps } from '../screen-manager/map-gem-screen-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const MapGemShowScreen = ({
  map_gem_id: mapGemId,
}: MapGemShowScreenProps): ReactNode => {
  const navigation = useMapGemScreenNavigation();
  const {
    map_gem: mapGem,
    loading,
    error,
    refresh,
  } = useMapGemDetail(mapGemId);
  const { rolling, error: rollError, roll } = useRollMapGem();
  const { error: activateError, activate_roll: activateRoll } =
    useActivateMapGemRoll();
  const [rollAnnouncement, setRollAnnouncement] = useState('');
  const [activatingGemId, setActivatingGemId] = useState<number | null>(null);

  const handleBack = (): void => {
    navigation.pop();
  };

  const handleEdit = (): void => {
    navigation.navigateTo(MapGemScreens.FORM, { map_gem_id: mapGemId });
  };

  const handleRoll = async (): Promise<void> => {
    const rolled = await roll(mapGemId);

    if (rolled) {
      setRollAnnouncement(`Rolled ${rolled.rolled_gem?.name ?? 'a new Gem'}.`);
      refresh();
    }
  };

  const handleActivateRoll = async (gemId: number): Promise<void> => {
    setActivatingGemId(gemId);
    const activated = await activateRoll(mapGemId, gemId);
    setActivatingGemId(null);

    if (activated) {
      setRollAnnouncement(
        `Roll #${activated.rolled_gem?.roll_number ?? ''} is now active.`
      );
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
        {activateError && <ApiErrorAlert apiError={activateError.message} />}

        <MapGemDetailBody
          map_gem={mapGem}
          on_activate_roll={(gemId) => void handleActivateRoll(gemId)}
          activating_gem_id={activatingGemId}
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
