import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import { LocationGemApiMessages } from '../api/enums/location-gem-api-messages';
import { useActivateLocationGemRoll } from '../api/hooks/use-activate-location-gem-roll';
import { useLocationGemDetail } from '../api/hooks/use-location-gem-detail';
import { useRollLocationGem } from '../api/hooks/use-roll-location-gem';
import LocationGemDetailBody from '../components/location-gem-detail-body';
import { LocationGemScreens } from '../screen-manager/location-gem-screen-constants';
import { useLocationGemScreenNavigation } from '../screen-manager/location-gem-screen-kit';
import { LocationGemShowScreenProps } from '../screen-manager/location-gem-screen-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const LocationGemShowScreen = ({
  location_gem_id: locationGemId,
}: LocationGemShowScreenProps): ReactNode => {
  const navigation = useLocationGemScreenNavigation();
  const {
    location_gem: locationGem,
    loading,
    error,
    refresh,
  } = useLocationGemDetail(locationGemId);
  const { rolling, error: rollError, roll } = useRollLocationGem();
  const { error: activateError, activate_roll: activateRoll } =
    useActivateLocationGemRoll();
  const [rollAnnouncement, setRollAnnouncement] = useState('');
  const [activatingGemId, setActivatingGemId] = useState<number | null>(null);

  const handleBack = (): void => {
    navigation.pop();
  };

  const handleEdit = (): void => {
    navigation.navigateTo(LocationGemScreens.FORM, {
      location_gem_id: locationGemId,
    });
  };

  const handleRoll = async (): Promise<void> => {
    const rolled = await roll(locationGemId);

    if (rolled) {
      setRollAnnouncement(`Rolled ${rolled.rolled_gem?.name ?? 'a new Gem'}.`);
      refresh();
    }
  };

  const handleActivateRoll = async (gemId: number): Promise<void> => {
    setActivatingGemId(gemId);
    const activated = await activateRoll(locationGemId, gemId);
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

    if (error || !locationGem) {
      return (
        <ApiErrorAlert
          apiError={error?.message ?? LocationGemApiMessages.Load}
        />
      );
    }

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
        {activateError && <ApiErrorAlert apiError={activateError.message} />}

        <LocationGemDetailBody
          location_gem={locationGem}
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
      title={locationGem?.name ?? 'Location Gem'}
      width={AdminPageWidth.Detail}
      header_actions={<AdminBackButton on_click={handleBack} />}
    >
      {renderContent()}
    </AdminPage>
  );
};

export default LocationGemShowScreen;
