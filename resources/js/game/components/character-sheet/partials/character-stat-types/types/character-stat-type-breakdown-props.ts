import { StatTypes } from '../../../enums/stat-types';

export default interface CharacterStatTypeBreakdownProps {
  stat_type: StatTypes;
  close_stat_type: () => void;
}
