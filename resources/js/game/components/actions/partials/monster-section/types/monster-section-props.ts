import { StateSetter } from '../../../../../../types/state-setter-type';

export default interface MonsterSectionProps {
  show_monster_stats: () => void;
  on_initiate_monster_fight: StateSetter<boolean>;
}
