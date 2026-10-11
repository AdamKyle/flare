import React, { ReactNode } from 'react';

import { useCharacterSkills } from '../../../character-sheet/skills/api/hooks/use-character-skills';
import { useOpenCharacterSkillDetail } from '../../../character-sheet/skills/hooks/use-open-character-skill-detail';

import { useGameData } from 'game-data/hooks/use-game-data';

import { formatNumberWithCommas } from 'game-utils/format-number';

import { ProgressBarSize } from 'ui/progress/enums/progress-bar-size';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

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
    <ProgressBar
      label={`${trainingSkill.name} (Lv. ${trainingSkill.level})`}
      value={trainingSkill.xp}
      max={Math.max(trainingSkill.xp_max, 1)}
      size={ProgressBarSize.THIN}
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
