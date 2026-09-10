import React, { ReactNode } from 'react';

import { SidePeekComponentRegistrationEnum } from '../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import MonsterGemEffectContextCard from '../../../game/reusable-components/monster/components/monster-gem-effect-context-card';
import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import { useMonsterScreenNavigation } from '../screen-manager/monster-screen-kit';
import { MonsterGemEffectContextScreenProps } from '../screen-manager/monster-screen-props';

import Card from 'ui/cards/card';

const MonsterGemEffectContextScreen = ({
  context,
}: MonsterGemEffectContextScreenProps): ReactNode => {
  const navigation = useMonsterScreenNavigation();
  const sidePeekEmitter = useSidePeekEmitter();

  const handleBack = (): void => {
    navigation.pop();
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

  return (
    <AdminPage
      title={context.label}
      width={AdminPageWidth.Detail}
      header_actions={<AdminBackButton on_click={handleBack} />}
    >
      <Card>
        <MonsterGemEffectContextCard
          context={context}
          navigation={{
            on_open_map_gem: handleOpenMapGem,
            on_open_location_gem: handleOpenLocationGem,
          }}
        />
      </Card>
    </AdminPage>
  );
};

export default MonsterGemEffectContextScreen;
