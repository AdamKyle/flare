import React, { ReactNode } from 'react';

import MonsterFormDefinition from '../api/definitions/monster-form-definition';
import MonsterFormContent from '../components/forms/monster-form-content';
import { MonsterScreens } from '../screen-manager/monster-screen-constants';
import { useMonsterScreenNavigation } from '../screen-manager/monster-screen-kit';
import { MonsterFormScreenProps } from '../screen-manager/monster-screen-props';

const MonsterFormScreen = ({
  monster_id: monsterId,
}: MonsterFormScreenProps): ReactNode => {
  const navigation = useMonsterScreenNavigation();

  const handleSaved = (monster: MonsterFormDefinition): void => {
    navigation.replaceWith(MonsterScreens.SHOW, { monster_id: monster.id });
  };

  const handleCancel = (): void => {
    navigation.pop();
  };

  return (
    <MonsterFormContent
      monster_id={monsterId}
      on_saved={handleSaved}
      on_cancel={handleCancel}
      embedded={false}
    />
  );
};

export default MonsterFormScreen;
