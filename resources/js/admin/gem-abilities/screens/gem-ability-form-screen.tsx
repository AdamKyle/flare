import React, { ReactNode } from 'react';

import GemAbilityFormDefinition from '../api/definitions/gem-ability-form-definition';
import GemAbilityFormContent from '../components/forms/gem-ability-form-content';
import { GemAbilityScreens } from '../screen-manager/gem-ability-screen-constants';
import { useGemAbilityScreenNavigation } from '../screen-manager/gem-ability-screen-kit';
import { GemAbilityFormScreenProps } from '../screen-manager/gem-ability-screen-props';

const GemAbilityFormScreen = ({
  gem_ability_id: gemAbilityId,
}: GemAbilityFormScreenProps): ReactNode => {
  const navigation = useGemAbilityScreenNavigation();

  const handleSaved = (gemAbility: GemAbilityFormDefinition): void => {
    navigation.replaceWith(GemAbilityScreens.SHOW, {
      gem_ability_id: gemAbility.id,
    });
  };

  const handleCancel = (): void => {
    navigation.pop();
  };

  return (
    <GemAbilityFormContent
      gem_ability_id={gemAbilityId}
      on_saved={handleSaved}
      on_cancel={handleCancel}
    />
  );
};

export default GemAbilityFormScreen;
