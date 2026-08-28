import React, { ReactNode, useState } from 'react';

import { GameMapSidePeekMessages } from './enums/game-map-side-peek-messages';
import GameMapLocationSidePeekProps from './types/game-map-location-side-peek-props';
import { useLocation } from '../../../locations/api/hooks/use-location';
import { LocationApiMessages } from '../../../locations/api/enums/location-api-messages';
import LocationDefinition from '../../../locations/api/definitions/location-definition';
import LocationFormScreen from '../../../locations/screens/location-form-screen';

import ApiErrorAlert from 'api-handler/components/api-error-alert';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import StackedCard from 'ui/cards/stacked-card';

const GameMapLocationSidePeek = ({
  game_map_id: gameMapId,
  location_id: locationId,
  location_marker: locationMarker,
  on_editor_changed: onEditorChanged,
  on_move_requested: onMoveRequested,
}: GameMapLocationSidePeekProps): ReactNode => {
  const { location, loading, error } = useLocation(gameMapId, locationId);
  const [editedLocation, setEditedLocation] =
    useState<LocationDefinition | null>(null);
  const [showEdit, setShowEdit] = useState(false);
  const [announcement, setAnnouncement] = useState('');

  const displayedLocation = editedLocation ?? location;

  const handleEdit = (): void => {
    setShowEdit(true);
  };

  const handleCloseEdit = (): void => {
    setShowEdit(false);
  };

  const handleSaved = (updated: LocationDefinition): void => {
    setEditedLocation(updated);
    void onEditorChanged();
    setShowEdit(false);
    setAnnouncement(GameMapSidePeekMessages.LocationSaved);
  };

  const handleMove = (): void => {
    onMoveRequested(locationId);
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !displayedLocation) {
      return (
        <ApiErrorAlert apiError={error?.message ?? LocationApiMessages.Load} />
      );
    }

    return (
      <div className="space-y-4 px-4">
        <Dl>
          <Dt>Name</Dt>
          <Dd>{displayedLocation.name}</Dd>
          <Dt>Description</Dt>
          <Dd>{displayedLocation.description}</Dd>
          <Dt>Coordinates</Dt>
          <Dd>
            X {displayedLocation.x}, Y {displayedLocation.y}
          </Dd>
          <Dt>Is Port</Dt>
          <Dd>{displayedLocation.is_port ? 'Yes' : 'No'}</Dd>
          <Dt>Players Can Enter</Dt>
          <Dd>{displayedLocation.can_players_enter ? 'Yes' : 'No'}</Dd>
          <Dt>Auto Battle</Dt>
          <Dd>{displayedLocation.can_auto_battle ? 'Yes' : 'No'}</Dd>
          <Dt>Corrupted</Dt>
          <Dd>{locationMarker.is_corrupted ? 'Yes' : 'No'}</Dd>
        </Dl>

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
