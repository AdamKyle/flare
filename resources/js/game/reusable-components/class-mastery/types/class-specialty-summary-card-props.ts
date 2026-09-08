import ClassMasteryDetailDefinition from './class-mastery-detail-definition';

import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';

export interface ClassSpecialtySummaryProgressDefinition {
  level: number;
  current_xp: number;
  required_xp: number;
  specialty_damage: number;
}

export default interface ClassSpecialtySummaryCardProps {
  class_mastery: ClassMasteryDetailDefinition;
  progress?: ClassSpecialtySummaryProgressDefinition;
  progress_variant?: ProgressBarVariant;
  on_click?: (id: number) => void;
  status_text?: string;
}
