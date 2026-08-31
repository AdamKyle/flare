import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import { GameMapSidePeekMessages } from './enums/game-map-side-peek-messages';
import GameMapLocationSidePeekProps from './types/game-map-location-side-peek-props';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import AdminQuestItemPresentationDefinition from '../../../items/api/definitions/admin-quest-item-presentation-definition';
import LocationDefinition from '../../../locations/api/definitions/location-definition';
import { LocationDetailRelatedItemDefinition } from '../../../locations/api/definitions/location-detail-definition';
import { LocationApiMessages } from '../../../locations/api/enums/location-api-messages';
import { useLocationDetail } from '../../../locations/api/hooks/use-location-detail';
import { useLocationQuestItems } from '../../../locations/api/hooks/use-location-quest-items';
import LocationDetailBody from '../../../locations/components/location-detail-body';
import LocationFormScreen from '../../../locations/screens/location-form-screen';

import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const GameMapLocationSidePeek = ({
  game_map_id: gameMapId,
  location_id: locationId,
  on_editor_changed: onEditorChanged,
  on_move_requested: onMoveRequested,
}: GameMapLocationSidePeekProps): ReactNode => {
  const sidePeekEmitter = useSidePeekEmitter();
  const { location, loading, error, refresh } = useLocationDetail(locationId);
  const questItems = useLocationQuestItems(locationId);
  const [showEdit, setShowEdit] = useState(false);
  const [announcement, setAnnouncement] = useState('');

  const handleEdit = (): void => {
    setShowEdit(true);
  };

  const handleCloseEdit = (): void => {
    setShowEdit(false);
  };

  const handleSaved = (updated: LocationDefinition): void => {
    void onEditorChanged();
    refresh();
    setShowEdit(false);
    setAnnouncement(`${updated.name ?? GameMapSidePeekMessages.LocationSaved}`);
  };

  const handleMove = (): void => {
    onMoveRequested(locationId);
  };

  const handleOpenQuestItem = (
    item: AdminQuestItemPresentationDefinition
  ): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_ITEM_DETAIL,
      {
        is_open: true,
        title: 'Item Details',
        allow_clicking_outside: true,
        item_id: item.item_id,
        on_item_changed: () => questItems.refresh(),
      }
    );
  };

  const handleOpenRelatedItem = (
    item: LocationDetailRelatedItemDefinition
  ): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_ITEM_DETAIL,
      {
        is_open: true,
        title: 'Item Details',
        allow_clicking_outside: true,
        item_id: item.id,
        on_item_changed: () => refresh(),
      }
    );
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

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !location) {
      return (
        <ApiErrorAlert apiError={error?.message ?? LocationApiMessages.Load} />
      );
    }

    return (
      <div className="space-y-4 px-4">
        <div className="flex items-center gap-3">
          <button
            type="button"
            onClick={handleEdit}
            className="focus-visible:ring-danube-400 bg-danube-600 hover:bg-danube-500 rounded-md px-3 py-2 text-sm font-medium text-white focus:outline-none focus-visible:ring-2"
          >
            Edit
          </button>
          <button
            type="button"
            onClick={handleMove}
            className="focus-visible:ring-glacier-400 border-glacier-300 text-glacier-700 hover:bg-glacier-50 dark:border-glacier-700 dark:text-glacier-200 dark:hover:bg-glacier-900 rounded-md border px-3 py-2 text-sm font-medium focus:outline-none focus-visible:ring-2"
          >
            Move
          </button>
        </div>

        <LocationDetailBody
          location={location}
          quest_items={questItems}
          on_open_related_item={handleOpenRelatedItem}
          on_open_quest_item={handleOpenQuestItem}
          on_open_map={handleOpenMap}
        />
      </div>
    );
  };

  const renderEdit = (): ReactNode => {
    if (!showEdit) {
      return null;
    }

    return (
      <StackedCard on_close={handleCloseEdit}>
        <LocationFormScreen
          game_map_id={gameMapId}
          location_id={locationId}
          initial_x={null}
          initial_y={null}
          on_saved={handleSaved}
          on_cancel={handleCloseEdit}
          embedded
        />
      </StackedCard>
    );
  };

  return (
    <>
      <p className="sr-only" role="status" aria-live="polite">
        {announcement}
      </p>
      {renderContent()}
      {renderEdit()}
    </>
  );
};

export default GameMapLocationSidePeek;
