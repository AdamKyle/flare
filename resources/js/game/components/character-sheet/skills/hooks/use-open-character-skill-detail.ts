import UseOpenCharacterSkillDetailDefinition from './definitions/use-open-character-skill-detail-definition';
import { SidePeekComponentRegistrationEnum } from '../../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../side-peeks/base/hooks/use-side-peek-emitter';
import CharacterSkillDefinition from '../api/definitions/character-skill-definition';

export const useOpenCharacterSkillDetail =
  (): UseOpenCharacterSkillDetailDefinition => {
    const sidePeekEmitter = useSidePeekEmitter();

    const openCharacterSkillDetail = (
      characterId: number,
      skill: CharacterSkillDefinition,
      onTrainingSkillsChanged: (
        trainingSkills: CharacterSkillDefinition[]
      ) => void
    ): void => {
      sidePeekEmitter.emit(
        SidePeek.SIDE_PEEK,
        SidePeekComponentRegistrationEnum.CHARACTER_SKILL_DETAIL,
        {
          is_open: true,
          title: skill.name,
          allow_clicking_outside: true,
          character_id: characterId,
          skill_id: skill.id,
          on_training_skills_changed: onTrainingSkillsChanged,
        }
      );
    };

    return { openCharacterSkillDetail };
  };
