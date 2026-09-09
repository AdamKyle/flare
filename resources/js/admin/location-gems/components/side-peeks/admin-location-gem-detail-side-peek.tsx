import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import AdminLocationGemDetailSidePeekProps from './types/admin-location-gem-detail-side-peek-props';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import { LocationGemApiMessages } from '../../api/enums/location-gem-api-messages';
import { useLocationGemDetail } from '../../api/hooks/use-location-gem-detail';
import LocationGemDetailBody from '../location-gem-detail-body';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const AdminLocationGemDetailSidePeek = ({
  location_gem_id: locationGemId,
}: AdminLocationGemDetailSidePeekProps): ReactNode => {
  const {
    location_gem: locationGem,
    loading,
    error,
  } = useLocationGemDetail(locationGemId);
  const sidePeekEmitter = useSidePeekEmitter();

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
      <LocationGemDetailBody
        location_gem={locationGem}
        is_side_peek
        navigation={{
          on_open_map: handleOpenMap,
          on_open_location: handleOpenLocation,
        }}
      />
    );
  };

  return (
    <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
      <div className="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5">
        {renderContent()}
      </div>
    </div>
  );
};

export default AdminLocationGemDetailSidePeek;
