import React, { ReactNode } from 'react';

import BuildingFormDefinition from '../api/definitions/building-form-definition';
import BuildingFormContent from '../components/forms/building-form-content';
import { BuildingScreens } from '../screen-manager/building-screen-constants';
import { useBuildingScreenNavigation } from '../screen-manager/building-screen-kit';
import { BuildingFormScreenProps } from '../screen-manager/building-screen-props';

const BuildingFormScreen = ({
  building_id: buildingId,
}: BuildingFormScreenProps): ReactNode => {
  const navigation = useBuildingScreenNavigation();

  const handleSaved = (building: BuildingFormDefinition): void => {
    navigation.replaceWith(BuildingScreens.BUILDING_SHOW, {
      building_id: building.id,
    });
  };

  const handleCancel = (): void => {
    navigation.pop();
  };

  return (
    <BuildingFormContent
      building_id={buildingId}
      on_saved={handleSaved}
      on_cancel={handleCancel}
    />
  );
};

export default BuildingFormScreen;
