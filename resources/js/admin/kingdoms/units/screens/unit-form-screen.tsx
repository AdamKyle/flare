import React, { ReactNode } from 'react';

import UnitFormDefinition from '../api/definitions/unit-form-definition';
import UnitFormContent from '../components/forms/unit-form-content';
import { UnitScreens } from '../screen-manager/unit-screen-constants';
import { useUnitScreenNavigation } from '../screen-manager/unit-screen-kit';
import { UnitFormScreenProps } from '../screen-manager/unit-screen-props';

const UnitFormScreen = ({
  unit_id: unitId,
}: UnitFormScreenProps): ReactNode => {
  const navigation = useUnitScreenNavigation();

  const handleSaved = (unit: UnitFormDefinition): void => {
    navigation.replaceWith(UnitScreens.UNIT_SHOW, { unit_id: unit.id });
  };

  const handleCancel = (): void => {
    navigation.pop();
  };

  return (
    <UnitFormContent
      unit_id={unitId}
      on_saved={handleSaved}
      on_cancel={handleCancel}
    />
  );
};

export default UnitFormScreen;
