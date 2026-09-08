import ApiErrorAlert from 'api-handler/components/api-error-alert';
import clsx from 'clsx';
import React, { ReactNode, useState } from 'react';

import { GameMapSidePeekMessages } from './enums/game-map-side-peek-messages';
import GameMapLocationSidePeekProps from './types/game-map-location-side-peek-props';
import { resolveSidePeekComponent } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-mapper';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import AdminQuestItemPresentationDefinition from '../../../items/api/definitions/admin-quest-item-presentation-definition';
import LocationDefinition from '../../../locations/api/definitions/location-definition';
import { LocationDetailRelatedItemDefinition } from '../../../locations/api/definitions/location-detail-definition';
import { LocationApiMessages } from '../../../locations/api/enums/location-api-messages';
import { useLocationDetail } from '../../../locations/api/hooks/use-location-detail';
import { useLocationQuestItems } from '../../../locations/api/hooks/use-location-quest-items';
import LocationDetailBody from '../../../locations/components/location-detail-body';
import { LocationNestedSelection } from '../../../locations/components/types/location-nested-selection';
import LocationFormScreen from '../../../locations/screens/location-form-screen';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const GameMapLocationSidePeek = ({
  game_map_id: gameMapId,
  location_id: locationId,
  on_editor_changed: onEditorChanged,
  on_move_requested: onMoveRequested,
}: GameMapLocationSidePeekProps): ReactNode => {
  const { location, loading, error, refresh } = useLocationDetail(locationId);
  const questItems = useLocationQuestItems(locationId);
  const [showEdit, setShowEdit] = useState(false);
  const [nestedSelection, setNestedSelection] =
    useState<LocationNestedSelection | null>(null);
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
    setNestedSelection({
      type: 'item',
      id: item.item_id,
      on_changed: () => questItems.refresh(),
    });
  };

  const handleOpenRelatedItem = (
    item: LocationDetailRelatedItemDefinition
  ): void => {
    setNestedSelection({
      type: 'item',
      id: item.id,
      on_changed: () => refresh(),
    });
  };

  const handleOpenMap = (id: number): void => {
    setNestedSelection({ type: 'map', id });
  };

  const handleCloseNested = (): void => {
    setNestedSelection(null);
  };

  const renderNestedDetail = (): ReactNode => {
    if (!nestedSelection) {
      return null;
    }

    if (nestedSelection.type === 'item') {
      const NestedItemDetail = resolveSidePeekComponent(
        SidePeekComponentRegistrationEnum.ADMIN_ITEM_DETAIL
      );

      return (
        <StackedCard
          on_close={handleCloseNested}
          aria_label="Item Details"
          content_mode={StackedCardContentMode.FULL_BLEED}
        >
          <NestedItemDetail
            is_open
            title="Item Details"
            item_id={nestedSelection.id}
            on_item_changed={nestedSelection.on_changed}
          />
        </StackedCard>
      );
    }

    const NestedGameMapDetail = resolveSidePeekComponent(
      SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_DETAIL
    );

    return (
      <StackedCard
        on_close={handleCloseNested}
        aria_label="Game Map Details"
        content_mode={StackedCardContentMode.FULL_BLEED}
      >
        <NestedGameMapDetail
          is_open
          title="Game Map Details"
          game_map_id={nestedSelection.id}
        />
      </StackedCard>
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
      <div className="space-y-4">
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

  const isStackActive = showEdit || nestedSelection !== null;

  return (
    <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
      <p className="sr-only" role="status" aria-live="polite">
        {announcement}
      </p>
      <div
        className={clsx(
          'min-h-0 flex-1 px-4 py-4',
          isStackActive ? 'overflow-hidden' : 'overflow-y-auto'
        )}
      >
        {renderContent()}
      </div>
      {renderEdit()}
      {renderNestedDetail()}
    </div>
  );
};

export default GameMapLocationSidePeek;
