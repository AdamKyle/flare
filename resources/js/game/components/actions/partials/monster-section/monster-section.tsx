import { isNil } from 'lodash';
import React, { ReactNode, useEffect, useState } from 'react';

import MonsterSectionProps from './types/monster-section-props';
import AttackButtonsContainer from '../../components/fight-section/attack-buttons-container';
import { HealthBarType } from '../../components/fight-section/enums/health-bar-type';
import HealthBar from '../../components/fight-section/health-bar';
import HealthBarContainer from '../../components/fight-section/health-bar-container';
import MonsterTopSection from '../../components/fight-section/monster-top-section';

import MonsterDefinition from 'game-data/api-data-definitions/monsters/monster-definition';
import { GameDataError } from 'game-data/components/game-data-error';
import { useGameData } from 'game-data/hooks/use-game-data';

import Button from 'ui/buttons/button';
import { ButtonGradientVarient } from 'ui/buttons/enums/button-gradient-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import GradientButton from 'ui/buttons/gradient-button';

const MonsterSection = ({
  show_monster_stats,
  has_initiate_monster_fight,
}: MonsterSectionProps): ReactNode => {
  const { gameData } = useGameData();

  const [currentIndex, setCurrentIndex] = useState(0);

  const [monsterName, setMonsterName] = useState<string | null>(null);

  const [monsterToFight, setMonsterToFight] = useState<number | null>(null);

  const monsters = gameData?.monsters;

  useEffect(() => {
    if (!monsters) {
      return;
    }

    setMonsterName(monsters[0].name);
  }, [monsters]);

  const handelMonsterSelection = () => {
    if (!monsters) {
      return;
    }

    if (!monsters[currentIndex]) {
      return;
    }

    const monsterToFight = monsters[currentIndex] as MonsterDefinition;

    setMonsterToFight(monsterToFight.id);
    has_initiate_monster_fight(true);
  };

  const handleNextIndex = (index: number) => {
    if (!monsters) {
      return;
    }

    if (!monsters[index]) {
      return;
    }

    const selectedMonster = monsters[index] as MonsterDefinition;

    setCurrentIndex(index);
    setMonsterToFight(null);
    setMonsterName(selectedMonster.name);
    has_initiate_monster_fight(false);
  };

  const handlePreviousAction = (index: number) => {
    if (!monsters) {
      return;
    }

    if (!monsters[index]) {
      return;
    }

    setCurrentIndex(index);

    const selectedMonster = monsters[index] as MonsterDefinition;

    setCurrentIndex(index);
    setMonsterToFight(null);
    setMonsterName(selectedMonster.name);
    has_initiate_monster_fight(false);
  };

  if (!monsters) {
    return <GameDataError />;
  }

  const renderMonsterFightSection = () => {
    if (isNil(monsterToFight)) {
      return (
        <div className={'text-center my-4'}>
          <Button
            on_click={handelMonsterSelection}
            label={'Initiate Fight'}
            variant={ButtonVariant.PRIMARY}
          />
        </div>
      );
    }

    return (
      <>
        <HealthBarContainer>
          <HealthBar
            current_health={100}
            max_health={100}
            name="Sewer Rat"
            health_bar_type={HealthBarType.ENEMY}
          />
          <HealthBar
            current_health={100}
            max_health={100}
            name="Credence"
            health_bar_type={HealthBarType.PLAYER}
          />
        </HealthBarContainer>
        <AttackButtonsContainer>
          <Button
            label="Attack"
            variant={ButtonVariant.PRIMARY}
            additional_css="w-full lg:w-1/3"
            on_click={() => {}}
          />
          <Button
            label="Cast"
            variant={ButtonVariant.PRIMARY}
            additional_css="w-full lg:w-1/3"
            on_click={() => {}}
          />
        </AttackButtonsContainer>
        <AttackButtonsContainer>
          <GradientButton
            label="Atk & Cast"
            gradient={ButtonGradientVarient.DANGER_TO_PRIMARY}
            additional_css="w-full lg:w-1/3"
            on_click={() => {}}
          />
          <GradientButton
            label="Cast & Atk"
            gradient={ButtonGradientVarient.PRIMARY_TO_DANGER}
            additional_css="w-full lg:w-1/3"
            on_click={() => {}}
          />
        </AttackButtonsContainer>
        <AttackButtonsContainer>
          <Button
            label="Defend"
            variant={ButtonVariant.PRIMARY}
            additional_css="w-full lg:w-1/3"
            on_click={() => {}}
          />
        </AttackButtonsContainer>
      </>
    );
  };

  return (
    <>
      <MonsterTopSection
        img_src="https://placecats.com/250/250"
        next_action={handleNextIndex}
        prev_action={handlePreviousAction}
        total_monsters={monsters.length - 1}
        current_index={currentIndex}
        view_monster_stats={show_monster_stats}
        monster_name={monsterName}
      />
      {renderMonsterFightSection()}
    </>
  );
};

export default MonsterSection;
