import WeaponMasteryDefinition from '../../api/definitions/weapon-mastery-definition';

import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';

export default interface CompactWeaponMasteryProps {
  weapon_mastery: WeaponMasteryDefinition;
  progress_variant: ProgressBarVariant;
}
