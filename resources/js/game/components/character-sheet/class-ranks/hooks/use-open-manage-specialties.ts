import { Screens } from 'configuration/screen-manager/screen-manager-constants';
import { useScreenNavigation } from 'configuration/screen-manager/screen-manager-kit';

import UseOpenManageSpecialtiesDefinition from './definitions/use-open-manage-specialties-definition';

export const useOpenManageSpecialties =
  (): UseOpenManageSpecialtiesDefinition => {
    const { navigateTo, pop } = useScreenNavigation();

    const openManageSpecialties = (
      characterId: number,
      initialSpecialtyId?: number
    ): void => {
      navigateTo(Screens.CLASS_SPECIALTIES, {
        character_id: characterId,
        initial_specialty_id: initialSpecialtyId ?? null,
        on_close: () => pop(),
      });
    };

    return { openManageSpecialties };
  };
