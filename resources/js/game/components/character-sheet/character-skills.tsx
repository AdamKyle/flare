import React, { ReactNode } from 'react';

import CharacterSkillDefinition from './skills/api/definitions/character-skill-definition';
import { useCharacterSkills } from './skills/api/hooks/use-character-skills';
import SkillCardList from './skills/components/skill-card-list';
import SkillCardListProps from './skills/components/types/skill-card-list-props';
import { useOpenCharacterSkillDetail } from './skills/hooks/use-open-character-skill-detail';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import PillTabs from 'ui/tabs/pill-tabs';
import { TabTupleFromProps } from 'ui/tabs/types/tab-item';

const CharacterSkills = (): ReactNode => {
  const { gameData } = useGameData();

  const characterId = gameData?.character?.id ?? 0;
  const userId = gameData?.character?.user_id ?? 0;

  const {
    training_skills: trainingSkills,
    crafting_skills: craftingSkills,
    loading,
    error,
    replace_training_skills: replaceTrainingSkills,
  } = useCharacterSkills({ character_id: characterId, user_id: userId });
  const { openCharacterSkillDetail } = useOpenCharacterSkillDetail();

  const handleOpenSkill = (skill: CharacterSkillDefinition) => {
    openCharacterSkillDetail(characterId, skill, replaceTrainingSkills);
  };

  if (loading) {
    return <InfiniteLoader />;
  }

  if (error !== null) {
    return <Alert variant={AlertVariant.DANGER}>{error}</Alert>;
  }

  const tabs: Readonly<
    TabTupleFromProps<[SkillCardListProps, SkillCardListProps]>
  > = [
    {
      label: 'Training Skills',
      component: SkillCardList,
      props: {
        skills: trainingSkills,
        empty_message: 'You have no Training Skills.',
        on_open_skill: handleOpenSkill,
      },
    },
    {
      label: 'Crafting Skills',
      component: SkillCardList,
      props: {
        skills: craftingSkills,
        empty_message: 'You have no Crafting Skills.',
        on_open_skill: handleOpenSkill,
      },
    },
  ];

  return <PillTabs tabs={tabs} ariaLabel="Skill categories" />;
};

export default CharacterSkills;
