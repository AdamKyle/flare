import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import AdminMapGemDetailSidePeekProps from './types/admin-map-gem-detail-side-peek-props';
import { MapGemApiMessages } from '../../api/enums/map-gem-api-messages';
import { useActivateMapGemRoll } from '../../api/hooks/use-activate-map-gem-roll';
import { useMapGemDetail } from '../../api/hooks/use-map-gem-detail';
import MapGemDetailBody from '../map-gem-detail-body';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';

/**
 * Admin Map Gem detail side-peek: a read-only view of the reusable Map Gem
 * detail body, addressable by profile ID, with roll activation wired in.
 * Used both for direct navigation and as nested `StackedCard` content from
 * other Admin detail surfaces (Bulk Roll results, Monster detail Gem effect
 * contexts).
 */
const AdminMapGemDetailSidePeek = ({
  map_gem_id: mapGemId,
}: AdminMapGemDetailSidePeekProps): ReactNode => {
  const {
    map_gem: mapGem,
    loading,
    error,
    refresh,
  } = useMapGemDetail(mapGemId);
  const { error: activateError, activate_roll: activateRoll } =
    useActivateMapGemRoll();
  const [activatingGemId, setActivatingGemId] = useState<number | null>(null);
  const [announcement, setAnnouncement] = useState('');

  const handleActivateRoll = async (gemId: number): Promise<void> => {
    setActivatingGemId(gemId);
    const activated = await activateRoll(mapGemId, gemId);
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

    if (error || !mapGem) {
      return (
        <ApiErrorAlert apiError={error?.message ?? MapGemApiMessages.Load} />
      );
    }

    return (
      <div className="space-y-4">
        {activateError && <ApiErrorAlert apiError={activateError.message} />}
        <MapGemDetailBody
          map_gem={mapGem}
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

export default AdminMapGemDetailSidePeek;
