import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import { SidePeekComponentRegistrationEnum } from '../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import MonsterDetail from '../../../game/reusable-components/monster/components/monster-detail';
import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import { MonsterApiMessages } from '../api/enums/monster-api-messages';
import { useMonsterDetail } from '../api/hooks/use-monster-detail';
import { MonsterScreens } from '../screen-manager/monster-screen-constants';
import { useMonsterScreenNavigation } from '../screen-manager/monster-screen-kit';
import { MonsterShowScreenProps } from '../screen-manager/monster-screen-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Card from 'ui/cards/card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const MonsterShowScreen = ({
  monster_id: monsterId,
}: MonsterShowScreenProps): ReactNode => {
  const navigation = useMonsterScreenNavigation();
  const sidePeekEmitter = useSidePeekEmitter();
  const { monster, loading, error } = useMonsterDetail(monsterId);

  const handleBack = (): void => {
    navigation.pop();
  };

  const handleEdit = (): void => {
    navigation.navigateTo(MonsterScreens.FORM, { monster_id: monsterId });
  };

  const handleOpenItem = (id: number): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_ITEM_DETAIL,
      {
        is_open: true,
        title: 'Item Details',
        allow_clicking_outside: true,
        item_id: id,
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

  const handleOpenMapGem = (profileId: number): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_MAP_GEM_DETAIL,
      {
        is_open: true,
        title: 'Map Gem Details',
        allow_clicking_outside: true,
        map_gem_id: profileId,
      }
    );
  };

  const handleOpenLocationGem = (profileId: number): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_LOCATION_GEM_DETAIL,
      {
        is_open: true,
        title: 'Location Gem Details',
        allow_clicking_outside: true,
        location_gem_id: profileId,
      }
    );
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !monster) {
      return (
        <ApiErrorAlert apiError={error?.message ?? MonsterApiMessages.Load} />
      );
    }

    return (
      <div className="flex flex-col gap-6">
        <div className="flex justify-start py-2">
          <Button
            label="Edit Monster"
            variant={ButtonVariant.PRIMARY}
            additional_css="text-sm px-3 py-1.5"
            on_click={handleEdit}
          />
        </div>

        <Card>
          <MonsterDetail
            monster={monster}
            navigation={{
              on_open_item: handleOpenItem,
              on_open_map: handleOpenMap,
              on_open_map_gem: handleOpenMapGem,
              on_open_location_gem: handleOpenLocationGem,
            }}
          />
        </Card>
      </div>
    );
  };

  return (
    <AdminPage
      title={monster?.identity.name ?? 'Monster'}
      width={AdminPageWidth.Detail}
      header_actions={<AdminBackButton on_click={handleBack} />}
    >
      {renderContent()}
    </AdminPage>
  );
};

export default MonsterShowScreen;
