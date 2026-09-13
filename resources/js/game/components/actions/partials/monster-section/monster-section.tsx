import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { isNil } from 'lodash';
import React, {
  ReactNode,
  useCallback,
  useEffect,
  useMemo,
  useState,
} from 'react';

import { useAttackMonster } from './api/hooks/use-attack-monster';
import { AttackType } from './enums/attack-type';
import { BattleType } from './enums/battle-type';
import MonsterImageProgression from './enums/monster-images';
import MonsterExplorationConfiguration from './monster-exploration-configuration';
import MonsterSectionProps from './types/monster-section-props';
import { getImageTierByIndex } from './util/monster-image-tier';
import AttackButtonsContainer from '../../components/fight-section/attack-buttons-container';
import AttackMessages from '../../components/fight-section/attack-messages';
import CharacterCombatStatus from '../../components/fight-section/character-combat-status';
import CharacterDeadAction from '../../components/fight-section/character-dead-action';
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
import TimerBar from 'ui/timer-bar/timer-bar';

const MonsterSection = ({
  show_monster_stats,
}: MonsterSectionProps): ReactNode => {
  const { gameData, listenForMonsterUpdates } = useGameData();
  const {
    loading,
    setRequestData,
    data,
    error,
    disableAttackButtons,
    awaitingAttackCooldownConfirmation,
    acknowledgeAttackCooldown,
    setReinitializeFight,
  } = useAttackMonster();

  const [currentIndex, setCurrentIndex] = useState(0);
  const [monsterName, setMonsterName] = useState<string | null>(null);
  const [monsterToFight, setMonsterToFight] = useState<number | null>(null);
  const [showExplorationConfiguration, setShowExplorationConfiguration] =
    useState(false);

  const monsters = useMemo(
    () => (Array.isArray(gameData?.monsters) ? gameData.monsters : []),
    [gameData?.monsters]
  );

  const isCharacterDead = gameData?.character?.is_dead ?? false;
  const attackCooldownSeconds = gameData?.character?.can_attack_again_at ?? 0;

  // Adjusted during render (not in an effect) so a new cooldown duration is
  // reflected on the same render it arrives, avoiding a one-render gap.
  const [prevAttackCooldownSeconds, setPrevAttackCooldownSeconds] = useState(
    attackCooldownSeconds
  );
  const [attackCooldownEndsAt, setAttackCooldownEndsAt] = useState<
    number | null
  >(
    attackCooldownSeconds > 0 ? Date.now() + attackCooldownSeconds * 1000 : null
  );
  const [attackCooldownTickedAt, setAttackCooldownTickedAt] = useState(() =>
    Date.now()
  );

  if (attackCooldownSeconds !== prevAttackCooldownSeconds) {
    const now = Date.now();

    setPrevAttackCooldownSeconds(attackCooldownSeconds);
    setAttackCooldownEndsAt(
      attackCooldownSeconds > 0 ? now + attackCooldownSeconds * 1000 : null
    );
    setAttackCooldownTickedAt(now);
  }

  useEffect(() => {
    if (attackCooldownEndsAt === null) {
      return;
    }

    const intervalId = setInterval(() => {
      setAttackCooldownTickedAt(Date.now());
    }, 100);

    return () => {
      clearInterval(intervalId);
    };
  }, [attackCooldownEndsAt]);

  const attackCooldownRemaining =
    attackCooldownEndsAt === null
      ? 0
      : Math.max(
          0,
          Math.round((attackCooldownEndsAt - attackCooldownTickedAt) / 100) / 10
        );

  const isOnAttackCooldown = !isCharacterDead && attackCooldownRemaining > 0;

  // Clears the handoff lock regardless of whether the cooldown websocket or
  // the fight-ending HTTP response is observed first.
  useEffect(() => {
    if (attackCooldownSeconds > 0 && awaitingAttackCooldownConfirmation) {
      acknowledgeAttackCooldown();
    }
  }, [
    attackCooldownSeconds,
    awaitingAttackCooldownConfirmation,
    acknowledgeAttackCooldown,
  ]);

  const isFightCooldownActive =
    isOnAttackCooldown || awaitingAttackCooldownConfirmation;

  useEffect(() => {
    if (!monsters || monsters.length === 0) {
      return;
    }

    setMonsterName(monsters[0].name);
  }, [monsters]);

  useEffect(() => {
    listenForMonsterUpdates();
  }, [listenForMonsterUpdates]);

  const handelMonsterSelection = (shouldFightAgain?: boolean) => {
    if (!monsters || !monsters[currentIndex] || !gameData?.character) {
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

    if (shouldFightAgain) {
      setReinitializeFight((prev) => !prev);
    }
  };

  const handleMonsterSelected = (index: number) => {
    if (!monsters || !monsters[index]) {
      return;
    }

    const selectedMonster = monsters[index] as MonsterDefinition;
    setCurrentIndex(index);
    setMonsterToFight(null);
    setMonsterName(selectedMonster.name);
  };

  const handleAttackMonster = (attackType: AttackType) => {
    if (!monsters || !monsters[currentIndex] || !gameData?.character) {
      return;
    }

    const selectedMonster = monsters[currentIndex] as MonsterDefinition;

    setRequestData({
      character_id: gameData.character.id,
      monster_id: selectedMonster.id,
      attack_type: attackType,
      battle_type: BattleType.ATTACK,
    });
  };

  const handleCloseExplorationConfiguration = useCallback(() => {
    setShowExplorationConfiguration(false);
  }, []);

  if (!gameData) {
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

  const handleClearBattleResults = () => {
    setMonsterToFight(null);
  };

  const renderMonsterFightSection = () => {
    if (isCharacterDead) {
      return (
        <CharacterDeadAction character_id={gameData?.character?.id || 0} />
      );
    }

    if (showExplorationConfiguration) {
      return (
        <MonsterExplorationConfiguration
          character_id={gameData?.character?.id || 0}
          on_close={handleCloseExplorationConfiguration}
        />
      );
    }

    if (loading) {
      return <InfiniteLoaderRoseDanube />;
    }

    if (error) {
      return <ApiErrorAlert apiError={error.message} />;
    }

    if (isNil(monsterToFight)) {
      return (
        <div className="my-4 text-center">
          <Button
            on_click={handelMonsterSelection}
            label="Initiate Fight"
            variant={ButtonVariant.PRIMARY}
            additional_css="block mx-auto w-48"
            disabled={isFightCooldownActive}
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

    const renderAttackAgainButton = () => {
      if (
        !data ||
        data.health.current_monster_health > 0 ||
        data.health.current_character_health <= 0
      ) {
        return (
          <div className={'w-full text-center'}>
            <Button
              label="Reset fight"
              variant={ButtonVariant.DANGER}
              additional_css="w-full lg:w-1/3 mt-2"
              on_click={handleClearBattleResults}
              disabled={isFightCooldownActive}
            />
          </div>
        );
      }

      return (
        <div className="flex w-full flex-col items-center gap-2 text-center lg:flex-row lg:justify-center">
          <Button
            label="Attack Again!"
            variant={ButtonVariant.PRIMARY}
            additional_css="w-full lg:w-1/3"
            on_click={() => handelMonsterSelection(true)}
            disabled={isFightCooldownActive}
          />
          <Button
            label="Clear"
            variant={ButtonVariant.DANGER}
            additional_css="w-full lg:w-1/3"
            on_click={handleClearBattleResults}
            disabled={isFightCooldownActive}
          />
        </div>
      );
    };

    const isAttackDisabled = disableAttackButtons || isFightCooldownActive;

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
              on_click={() => handleAttackMonster(AttackType.ATTACK)}
              disabled={isAttackDisabled}
            />
            <Button
              label="Cast"
              variant={ButtonVariant.PRIMARY}
              additional_css="w-full lg:w-1/3"
              on_click={() => handleAttackMonster(AttackType.CAST)}
              disabled={isAttackDisabled}
            />
          </AttackButtonsContainer>
          <AttackButtonsContainer>
            <GradientButton
              label="Atk & Cast"
              gradient={ButtonGradientVarient.DANGER_TO_PRIMARY}
              additional_css="w-full lg:w-1/3"
              on_click={() => handleAttackMonster(AttackType.ATTACK_AND_CAST)}
              disabled={isAttackDisabled}
            />
            <GradientButton
              label="Cast & Atk"
              gradient={ButtonGradientVarient.PRIMARY_TO_DANGER}
              additional_css="w-full lg:w-1/3"
              on_click={() => handleAttackMonster(AttackType.CAST_AND_ATTACK)}
              disabled={isAttackDisabled}
            />
          </AttackButtonsContainer>
          <AttackButtonsContainer>
            <Button
              label="Defend"
              variant={ButtonVariant.PRIMARY}
              additional_css="w-full lg:w-1/3"
              on_click={() => handleAttackMonster(AttackType.DEFEND)}
              disabled={isAttackDisabled}
            />
          </AttackButtonsContainer>
        </>
      );
    };

    const renderAttackCooldownBar = () => {
      if (!isOnAttackCooldown) {
        return null;
      }

      return (
        <TimerBar
          length={attackCooldownSeconds}
          remaining={attackCooldownRemaining}
          precise_time
          title="Next Attack"
          additional_css="my-2"
        />
      );
    };

    return (
      <>
        <CharacterCombatStatus />
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
        {renderAttackCooldownBar()}
        {renderAttackButtons()}
        {renderAttackAgainButton()}
        <AttackMessages messages={data?.attack_messages || []} />
      </>
    );
  };

  return (
    <>
      <MonsterTopSection
        img_src={getMonsterImage()}
        total_monsters={monsters.length - 1}
        current_index={currentIndex}
        view_monster_stats={show_monster_stats}
        monster_name={monsterName}
        monsters={monsters}
        select_action={handleMonsterSelected}
      />
      {renderMonsterFightSection()}
    </>
  );
};

export default MonsterSection;
