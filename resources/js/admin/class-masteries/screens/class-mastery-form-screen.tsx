import React, { ReactNode } from 'react';

import ClassMasteryFormDefinition from '../api/definitions/class-mastery-form-definition';
import ClassMasteryFormContent from '../components/forms/class-mastery-form-content';
import { ClassMasteryScreens } from '../screen-manager/class-mastery-screen-constants';
import { useClassMasteryScreenNavigation } from '../screen-manager/class-mastery-screen-kit';
import { ClassMasteryFormScreenProps } from '../screen-manager/class-mastery-screen-props';

const ClassMasteryFormScreen = ({
  class_mastery_id: classMasteryId,
}: ClassMasteryFormScreenProps): ReactNode => {
  const navigation = useClassMasteryScreenNavigation();

  const handleSaved = (classMastery: ClassMasteryFormDefinition): void => {
    navigation.replaceWith(ClassMasteryScreens.SHOW, {
      class_mastery_id: classMastery.id,
    });
  };

  const handleCancel = (): void => {
    navigation.pop();
  };

  return (
    <ClassMasteryFormContent
      class_mastery_id={classMasteryId}
      on_saved={handleSaved}
      on_cancel={handleCancel}
      embedded={false}
    />
  );
};

export default ClassMasteryFormScreen;
