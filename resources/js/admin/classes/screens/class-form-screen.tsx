import React, { ReactNode } from 'react';

import ClassFormDefinition from '../api/definitions/class-form-definition';
import ClassFormContent from '../components/forms/class-form-content';
import { ClassScreens } from '../screen-manager/class-screen-constants';
import { useClassScreenNavigation } from '../screen-manager/class-screen-kit';
import { ClassFormScreenProps } from '../screen-manager/class-screen-props';

const ClassFormScreen = ({
  class_id: classId,
}: ClassFormScreenProps): ReactNode => {
  const navigation = useClassScreenNavigation();

  const handleSaved = (gameClass: ClassFormDefinition): void => {
    navigation.replaceWith(ClassScreens.SHOW, { class_id: gameClass.id });
  };

  const handleCancel = (): void => {
    navigation.pop();
  };

  return (
    <ClassFormContent
      class_id={classId}
      on_saved={handleSaved}
      on_cancel={handleCancel}
      embedded={false}
    />
  );
};

export default ClassFormScreen;
