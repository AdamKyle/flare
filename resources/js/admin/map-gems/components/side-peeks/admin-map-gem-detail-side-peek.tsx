import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import AdminMapGemDetailSidePeekProps from './types/admin-map-gem-detail-side-peek-props';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import { MapGemApiMessages } from '../../api/enums/map-gem-api-messages';
import { useMapGemDetail } from '../../api/hooks/use-map-gem-detail';
import MapGemDetailBody from '../map-gem-detail-body';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const AdminMapGemDetailSidePeek = ({
  map_gem_id: mapGemId,
}: AdminMapGemDetailSidePeekProps): ReactNode => {
  const { map_gem: mapGem, loading, error } = useMapGemDetail(mapGemId);
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
      <MapGemDetailBody
        map_gem={mapGem}
        is_side_peek
        navigation={{ on_open_map: handleOpenMap }}
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

export default AdminMapGemDetailSidePeek;
