import { StateSetter } from '../../../../../../types/state-setter-type';

export default interface MonsterSectionProps {
  show_monster_stats: () => void;
  has_initiate_monster_fight: StateSetter<boolean>;
}
