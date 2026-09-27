import React, { ReactNode } from 'react';

import SkillFormDefinition from '../api/definitions/skill-form-definition';
import SkillFormContent from '../components/forms/skill-form-content';
import { SkillScreens } from '../screen-manager/skill-screen-constants';
import { useSkillScreenNavigation } from '../screen-manager/skill-screen-kit';
import { SkillFormScreenProps } from '../screen-manager/skill-screen-props';

const SkillFormScreen = ({
  skill_id: skillId,
}: SkillFormScreenProps): ReactNode => {
  const navigation = useSkillScreenNavigation();

  const handleSaved = (skill: SkillFormDefinition): void => {
    navigation.replaceWith(SkillScreens.SHOW, { skill_id: skill.id });
  };

  const handleCancel = (): void => {
    navigation.pop();
  };

  return (
    <SkillFormContent
      skill_id={skillId}
      on_saved={handleSaved}
      on_cancel={handleCancel}
    />
  );
};

export default SkillFormScreen;
