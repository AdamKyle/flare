import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import AdminLocationGemDetailSidePeekProps from './types/admin-location-gem-detail-side-peek-props';
import { LocationGemApiMessages } from '../../api/enums/location-gem-api-messages';
import { useActivateLocationGemRoll } from '../../api/hooks/use-activate-location-gem-roll';
import { useLocationGemDetail } from '../../api/hooks/use-location-gem-detail';
import LocationGemDetailBody from '../location-gem-detail-body';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';

/**
 * Admin Location Gem detail side-peek: a read-only view of the reusable
 * Location Gem detail body, addressable by profile ID, with roll activation
 * wired in. Used both for direct navigation and as nested `StackedCard`
 * content from other Admin detail surfaces (Bulk Roll results, Monster
 * detail Gem effect contexts).
 */
const AdminLocationGemDetailSidePeek = ({
  location_gem_id: locationGemId,
}: AdminLocationGemDetailSidePeekProps): ReactNode => {
  const {
    location_gem: locationGem,
    loading,
    error,
    refresh,
  } = useLocationGemDetail(locationGemId);
  const { error: activateError, activate_roll: activateRoll } =
    useActivateLocationGemRoll();
  const [activatingGemId, setActivatingGemId] = useState<number | null>(null);
  const [announcement, setAnnouncement] = useState('');

  const handleActivateRoll = async (gemId: number): Promise<void> => {
    setActivatingGemId(gemId);
    const activated = await activateRoll(locationGemId, gemId);
    setActivatingGemId(null);

    if (activated) {
      setAnnouncement(
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
      <div className="space-y-4">
        {activateError && <ApiErrorAlert apiError={activateError.message} />}
        <LocationGemDetailBody
          location_gem={locationGem}
          on_activate_roll={(gemId) => void handleActivateRoll(gemId)}
          activating_gem_id={activatingGemId}
        />
      </div>
    );
  };

  return (
    <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
      <p className="sr-only" role="status" aria-live="polite">
        {announcement}
      </p>
      <div className="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5">
        {renderContent()}
      </div>
    </div>
  );
};

export default AdminLocationGemDetailSidePeek;
