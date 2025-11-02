import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { isNil } from 'lodash';
import React, { ReactNode, useEffect } from 'react';

import { useFetchMonsterStatsApi } from './api/hooks/use-fetch-monster-stats-api';
import MonsterAmbushCounterSection from './partials/monster-ambush-counter-section';
import MonsterBasicStatsSection from './partials/monster-basic-stats-section';
import MonsterCoreSection from './partials/monster-core-section';
import MonsterCoreStatsSection from './partials/monster-core-stats-section';
import MonsterDevouringSection from './partials/monster-devouring-section';
import MonsterElementalAtonementSection from './partials/monster-element-atonement-section';
import MonsterRaidSpecialAttackSection from './partials/monster-raid-special-attack-section';
import MonsterResistanceSection from './partials/monster-resistance-section';
import MonsterRewardsSection from './partials/monster-reward-section';
import MonsterSkillSection from './partials/monster-skill-section';
import MonsterStatSectionProps from './types/monster-stat-section-props';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

export const MonsterStatSection = ({
  monster_id,
  toggle_monster_stat_visibility,
}: MonsterStatSectionProps): ReactNode => {
  const { gameData } = useGameData();
  const { loading, data, error, setRequestParams } = useFetchMonsterStatsApi();

  useEffect(() => {
    if (monster_id === 0 || !gameData?.character) {
      return;
    }

    setRequestParams({
      character_id: gameData.character.id,
      monster_id,
    });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [gameData?.character, monster_id]);

  if (isNil(data) || loading) {
    return (
      <ContainerWithTitle
        manageSectionVisibility={() => toggle_monster_stat_visibility(0)}
        title={'Fetching Monster'}
      >
        <Card>
          <InfiniteLoader />
        </Card>
      </ContainerWithTitle>
    );
  }

  if (!isNil(error)) {
    return (
      <ContainerWithTitle
        manageSectionVisibility={() => toggle_monster_stat_visibility(0)}
        title={'something went wrong'}
      >
        <Card>
          <ApiErrorAlert apiError={error.message} />
        </Card>
      </ContainerWithTitle>
    );
  }

  const renderCelestialAlert = () => {
    if (!data.is_celestial_entity) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.INFO}>
        <strong>This Creature is a celestial</strong>: You will find the
        conjuration cost in shards to conjure this beast. You cannot encounter
        this beast in the wild unless you trigger a spawn by either moving
        (small chance) or unless it's Celestial Day, in which case any form of
        movement has an 80% chance to conjure one. Players who completed Quest X
        can use /PCT command to instantly travel to it. Killing it in one hit is
        advised or it will move and heal for full health.
      </Alert>
    );
  };

  const renderRaidMonsterAlert = () => {
    if (!data.is_raid_monster) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.INFO}>
        <strong>This creature is a raid monster</strong>: These creatures live
        at specific locations on specific maps while a raid is taking place.
        These creatures can be strong and hard to take down, but they drop quest
        items to progress raid story line quests that lead towards unlocking
        cosmetic based rewards. You can fight them while at the specific
        location(s) and selecting them from the drop down.
      </Alert>
    );
  };

  const renderRaidBossAlert = () => {
    if (!data.is_raid_boss) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.INFO}>
        <strong>This creature is a raid boss</strong>: This beast lives at a
        specific location while a raid is in progress. These creatures cannot be
        taken down alone, and require many players to work together to bring the
        beast down! The player who lands the last hit, gets a full set of gear
        the raid boss raid dropping. Come prepared to die child!
      </Alert>
    );
  };

  return (
    <ContainerWithTitle
      manageSectionVisibility={() => toggle_monster_stat_visibility(0)}
      title={data.name}
    >
      <Card>
        {renderCelestialAlert()}
        {renderRaidMonsterAlert()}
        {renderRaidBossAlert()}

        <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-2">
          <div>
            <MonsterCoreSection monster={data} />

            <MonsterRewardsSection monster={data} />

            <MonsterBasicStatsSection monster={data} />

            <MonsterRaidSpecialAttackSection monster={data} />

            <MonsterAmbushCounterSection monster={data} />

            <MonsterElementalAtonementSection monster={data} />
          </div>

          <div>
            <MonsterCoreStatsSection monster={data} />

            <MonsterSkillSection monster={data} />

            <MonsterResistanceSection monster={data} />

            <MonsterDevouringSection monster={data} />
          </div>
        </div>
      </Card>
    </ContainerWithTitle>
  );
};
