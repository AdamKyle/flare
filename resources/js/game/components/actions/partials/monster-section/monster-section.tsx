import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { isNil } from 'lodash';
import React, { ReactNode, useEffect, useState } from 'react';

import { useAttackMonster } from './api/hooks/use-attack-monster';
import { AttackType } from './enums/attack-type';
import { BattleType } from './enums/battle-type';
import MonsterImageProgression from './enums/monster-images';
import MonsterExplorationConfiguration from './monster-exploration-configuration';
import MonsterSectionProps from './types/monster-section-props';
import { getImageTierByIndex } from './util/monster-image-tier';
import AttackButtonsContainer from '../../components/fight-section/attack-buttons-container';
import AttackMessages from '../../components/fight-section/attack-messages';
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
import InfiniteLoaderRoseDanube from 'ui/infinite-scroll/infinite-loader-rose-danube';

const MonsterSection = ({
  show_monster_stats,
}: MonsterSectionProps): ReactNode => {
  const { gameData, listenForMonsterUpdates } = useGameData();
  const { loading, setRequestData, data, error } = useAttackMonster();

  const [currentIndex, setCurrentIndex] = useState(0);
  const [monsterName, setMonsterName] = useState<string | null>(null);
  const [monsterToFight, setMonsterToFight] = useState<number | null>(null);
  const [showExplorationConfiguration, setShowExplorationConfiguration] =
    useState(false);

  const monsters = gameData?.monsters;

  useEffect(() => {
    if (!monsters || monsters.length === 0) {
      return;
    }

    setMonsterName(monsters[0].name);
  }, [monsters]);

  listenForMonsterUpdates();

  const handelMonsterSelection = () => {
    if (!monsters || !monsters[currentIndex] || !gameData.character) {
      return;
    }

    const selectedMonster = monsters[currentIndex] as MonsterDefinition;
    setMonsterToFight(selectedMonster.id);

    setRequestData({
      character_id: gameData.character.id,
      monster_id: selectedMonster.id,
      attack_type: AttackType.ATTACK,
      battle_type: BattleType.INITIATE,
    });
  };

  const handleNextIndex = (index: number) => {
    if (!monsters || !monsters[index]) {
      return;
    }

    const selectedMonster = monsters[index] as MonsterDefinition;
    setCurrentIndex(index);
    setMonsterToFight(null);
    setMonsterName(selectedMonster.name);
  };

  const handlePreviousAction = (index: number) => {
    if (!monsters || !monsters[index]) {
      return;
    }

    const selectedMonster = monsters[index] as MonsterDefinition;
    setCurrentIndex(index);
    setMonsterToFight(null);
    setMonsterName(selectedMonster.name);
  };

  if (!monsters) {
    return <GameDataError />;
  }

  const handleSetupExploration = () => {
    setShowExplorationConfiguration(true);
  };

  const getMonsterImage = () => {
    if (!monsters || monsters.length === 0) {
      return MonsterImageProgression[0];
    }

    const tierIndex = getImageTierByIndex(
      currentIndex,
      monsters.length,
      MonsterImageProgression.length
    );

    return MonsterImageProgression[tierIndex];
  };

  const renderMonsterFightSection = () => {
    if (showExplorationConfiguration) {
      return <MonsterExplorationConfiguration />;
    }

    if (loading) {
      return <InfiniteLoaderRoseDanube />;
    }

    if (error) {
      return <ApiErrorAlert apiError={error.message} />;
    }

    if (isNil(monsterToFight)) {
      return (
        <div className="text-center my-4">
          <Button
            on_click={handelMonsterSelection}
            label="Initiate Fight"
            variant={ButtonVariant.PRIMARY}
            additional_css="block mx-auto w-48"
          />
          <Button
            on_click={handleSetupExploration}
            label="Setup Exploration"
            variant={ButtonVariant.SUCCESS}
            additional_css="block mx-auto w-48 mt-4"
          />
        </div>
      );
    }

    const renderAttackButtons = () => {
      if (
        !data ||
        data.health.current_character_health <= 0 ||
        data.health.current_monster_health <= 0
      ) {
        return null;
      }

      return (
        <>
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
        <HealthBarContainer>
          <HealthBar
            current_health={data?.health.current_monster_health || 0}
            max_health={data?.health.max_monster_health || 0}
            name={monsterName || 'Unknown'}
            health_bar_type={HealthBarType.ENEMY}
          />
          <HealthBar
            current_health={data?.health.current_character_health || 0}
            max_health={data?.health.max_character_health || 0}
            name={gameData?.character?.name || 'Unknown'}
            health_bar_type={HealthBarType.PLAYER}
          />
        </HealthBarContainer>
        {renderAttackButtons()}
        <div className="mt-4 rounded-lg bg-gray-100 dark:bg-gray-700 p-4 text-sm border border-solid border-gray-200 dark:border-gray-800 ">
          <AttackMessages messages={data?.attack_messages || []} />
        </div>
      </>
    );
  };

  return (
    <>
      <MonsterTopSection
        img_src={getMonsterImage()}
        next_action={handleNextIndex}
        prev_action={handlePreviousAction}
        total_monsters={monsters.length - 1}
        current_index={currentIndex}
        view_monster_stats={show_monster_stats}
        monster_name={monsterName}
        monsters={monsters}
        select_action={handleNextIndex}
      />
      {renderMonsterFightSection()}
    </>
  );
};

export default MonsterSection;
