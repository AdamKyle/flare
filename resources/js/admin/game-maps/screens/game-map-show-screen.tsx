import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import { SidePeekComponentRegistrationEnum } from '../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import GameMapDetail from '../../../game/reusable-components/game-map/components/game-map-detail';
import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import { GameMapApiMessages } from '../api/enums/game-map-api-messages';
import { useGameMap } from '../api/hooks/use-game-map';
import GameMapRelatedDataNavigation from '../components/game-map-related-data-navigation';
import { GameMapSidePeekMessages } from '../components/side-peeks/enums/game-map-side-peek-messages';
import { GameMapScreens } from '../screen-manager/game-map-screen-constants';
import { useGameMapScreenNavigation } from '../screen-manager/game-map-screen-kit';
import { GameMapShowScreenProps } from '../screen-manager/game-map-screen-props';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Card from 'ui/cards/card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const GameMapShowScreen = ({
  game_map_id: gameMapId,
}: GameMapShowScreenProps): ReactNode => {
  const navigation = useGameMapScreenNavigation();
  const sidePeekEmitter = useSidePeekEmitter();
  const { game_map: gameMap, loading, error, refresh } = useGameMap(gameMapId);
  const [announcement, setAnnouncement] = useState('');
  const hasTiles = (gameMap?.tiles.length ?? 0) > 0;

  const handleBack = (): void => {
    navigation.pop();
  };

  const handleEditLocations = (): void => {
    if (!hasTiles) {
      return;
    }

    navigation.navigateTo(GameMapScreens.EDITOR, { game_map_id: gameMapId });
  };

  const handleMapSaved = (): void => {
    refresh();
    setAnnouncement(GameMapSidePeekMessages.GameMapSaved);
  };

  const handleEditMap = (): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_FORM,
      {
        is_open: true,
        title: 'Edit Game Map',
        allow_clicking_outside: true,
        game_map_id: gameMapId,
        on_saved: handleMapSaved,
      }
    );
  };

  const renderEditorAction = (): ReactNode => {
    if (!hasTiles) {
      return null;
    }

    return (
      <Button
        label="Edit Locations"
        variant={ButtonVariant.PRIMARY}
        on_click={handleEditLocations}
      />
    );
  };

  const renderProcessingStatus = (): ReactNode => {
    if (hasTiles) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.INFO}>
        Map tile processing is not complete. The coordinate editor will be
        available after processing finishes and this page is refreshed.
      </Alert>
    );
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !gameMap) {
      return (
        <ApiErrorAlert apiError={error?.message ?? GameMapApiMessages.Load} />
      );
    }

    return (
      <div className="flex flex-col gap-6">
        <div className="flex flex-wrap items-center gap-3">
          <Button
            label="Edit Map"
            variant={ButtonVariant.PRIMARY}
            on_click={handleEditMap}
          />
          {renderEditorAction()}
        </div>

        <Card>
          <div className="flex flex-col gap-6">
            {renderProcessingStatus()}

            <div className="lg:flex lg:items-start lg:gap-6">
              <GameMapRelatedDataNavigation game_map_id={gameMapId} />
              <div className="min-w-0 lg:flex-1">
                <GameMapDetail game_map={gameMap} split_layout />
              </div>
            </div>
          </div>
        </Card>
      </div>
    );
  };

  return (
    <AdminPage
      title={gameMap?.name ?? 'Game Map'}
      width={AdminPageWidth.Detail}
      header_actions={<AdminBackButton on_click={handleBack} />}
    >
      <p className="sr-only" role="status" aria-live="polite">
        {announcement}
      </p>
      {renderContent()}
    </AdminPage>
  );
};

export default GameMapShowScreen;
