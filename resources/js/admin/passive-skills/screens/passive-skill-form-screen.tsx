import React, { ReactNode } from 'react';

import PassiveSkillFormDefinition from '../api/definitions/passive-skill-form-definition';
import PassiveSkillFormContent from '../components/forms/passive-skill-form-content';
import { PassiveSkillScreens } from '../screen-manager/passive-skill-screen-constants';
import { usePassiveSkillScreenNavigation } from '../screen-manager/passive-skill-screen-kit';
import { PassiveSkillFormScreenProps } from '../screen-manager/passive-skill-screen-props';

const PassiveSkillFormScreen = ({
  passive_skill_id: passiveSkillId,
}: PassiveSkillFormScreenProps): ReactNode => {
  const navigation = usePassiveSkillScreenNavigation();

  const handleSaved = (passiveSkill: PassiveSkillFormDefinition): void => {
    navigation.replaceWith(PassiveSkillScreens.SHOW, {
      passive_skill_id: passiveSkill.id,
    });
  };

  const handleCancel = (): void => {
    navigation.pop();
  };

  return (
    <PassiveSkillFormContent
      passive_skill_id={passiveSkillId}
      on_saved={handleSaved}
      on_cancel={handleCancel}
    />
  );
};

export default PassiveSkillFormScreen;
