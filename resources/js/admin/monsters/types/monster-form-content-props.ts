import MonsterFormDefinition from '../api/definitions/monster-form-definition';

export default interface MonsterFormContentProps {
  monster_id: number | null;
  on_saved: (monster: MonsterFormDefinition) => void;
  on_cancel: () => void;
  embedded: boolean;
}
