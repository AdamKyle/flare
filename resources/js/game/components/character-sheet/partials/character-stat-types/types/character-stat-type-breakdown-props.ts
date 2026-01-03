import { StatTypes } from '../../../enums/stat-types';

export default interface CharacterStatTypeBreakdownProps {
  stat_type: StatTypes | null;
  close_stat_type: () => void;
}
