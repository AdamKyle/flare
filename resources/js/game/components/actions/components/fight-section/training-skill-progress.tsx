import React, { ReactNode } from 'react';

import XpBar from '../../../../reusable-components/progress/xp-bar';
import { useCharacterSkills } from '../../../character-sheet/skills/api/hooks/use-character-skills';
import { useOpenCharacterSkillDetail } from '../../../character-sheet/skills/hooks/use-open-character-skill-detail';

import { useGameData } from 'game-data/hooks/use-game-data';

import { formatNumberWithCommas } from 'game-utils/format-number';

import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';

const TrainingSkillProgress = (): ReactNode => {
  const { gameData } = useGameData();
  const character = gameData?.character ?? null;
  const {
    training_skills: trainingSkills,
    replace_training_skills: replaceTrainingSkills,
  } = useCharacterSkills({
    character_id: character?.id ?? 0,
    user_id: character?.user_id ?? 0,
  });
  const { openCharacterSkillDetail } = useOpenCharacterSkillDetail();

  if (!character) {
    return null;
  }

  const trainingSkill = trainingSkills.find((skill) => skill.is_training);

  if (!trainingSkill) {
    return null;
  }

  const isMaxLevel = trainingSkill.level >= trainingSkill.max_level;

  return (
    <XpBar
      current_xp={trainingSkill.xp}
      max_xp={Math.max(trainingSkill.xp_max, 1)}
      label={`${trainingSkill.name} (Lv. ${trainingSkill.level})`}
      variant={ProgressBarVariant.TERRACOTTA}
      value_label={
        isMaxLevel
          ? 'Max'
          : `${formatNumberWithCommas(trainingSkill.xp)} / ${formatNumberWithCommas(trainingSkill.xp_max)} XP`
      }
      aria_label={`${trainingSkill.name} level ${trainingSkill.level} Skill XP progress`}
      on_label_click={() =>
        openCharacterSkillDetail(
          character.id,
          trainingSkill,
          replaceTrainingSkills
        )
      }
    />
  );
};

export default TrainingSkillProgress;
