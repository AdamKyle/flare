import React, { ReactNode } from 'react';

import RaceDefinition from '../api/definitions/race-definition';
import RaceFormContent from '../components/forms/race-form-content';
import { RaceScreens } from '../screen-manager/race-screen-constants';
import { useRaceScreenNavigation } from '../screen-manager/race-screen-kit';
import { RaceFormScreenProps } from '../screen-manager/race-screen-props';

const RaceFormScreen = ({
  race_id: raceId,
}: RaceFormScreenProps): ReactNode => {
  const navigation = useRaceScreenNavigation();

  const handleSaved = (race: RaceDefinition): void => {
    navigation.replaceWith(RaceScreens.SHOW, { race_id: race.id });
  };

  const handleCancel = (): void => {
    navigation.pop();
  };

  return (
    <RaceFormContent
      race_id={raceId}
      on_saved={handleSaved}
      on_cancel={handleCancel}
      embedded={false}
    />
  );
};

export default RaceFormScreen;
