import CharacterSkillDefinition from '../../../character-sheet/skills/api/definitions/character-skill-definition';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface CharacterSkillDetailSidePeekProps extends SidePeekProps {
  character_id: number;
  skill_id: number;
  on_training_skills_changed: (
    trainingSkills: CharacterSkillDefinition[]
  ) => void;
}
