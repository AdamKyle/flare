import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { isNil } from 'lodash';
import React, { ReactNode, useEffect } from 'react';

import { useFetchMonsterStatsApi } from './api/hooks/use-fetch-monster-stats-api';
import MonsterCoreSection from './partials/monster-core-section';
import MonsterStatSectionProps from './types/monster-stat-section-props';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';

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

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6">
          <div>
            <MonsterCoreSection monster={data} />

            <h3 className="text-danube-500 dark:text-danube-700 mt-5">
              Basic Stats
            </h3>
            <Separator />
            <Dl>
              <Dt>Health Range:</Dt>
              <Dd>100 - 200</Dd>
              <Dt>Attack Range:</Dt>
              <Dd>100 - 200</Dd>
              <Dt>Increase Damage By:</Dt>
              <Dd>10%</Dd>
              <Dt>Max Spell Damage:</Dt>
              <Dd>350</Dd>
              <Dt>Max Affix Damage:</Dt>
              <Dd>350</Dd>
              <Dt>Entrancing Chance:</Dt>
              <Dd>8%</Dd>
              <Dt>Max Healing:</Dt>
              <Dd>1,800,000</Dd>
              <Dt>Armour Class (Defence):</Dt>
              <Dd>150</Dd>
            </Dl>

            <h3 className="text-danube-500 dark:text-danube-700 mt-5">
              Raid Special Attack
            </h3>
            <Separator />
            <Dl>
              <Dt>Attack Name:</Dt>
              <Dd>Some Attack</Dd>
              <Dt>Details:</Dt>
              <Dd>Deals damage to players</Dd>
            </Dl>

            <h3 className="text-danube-500 dark:text-danube-700 mt-5">
              Ambush and Counter
            </h3>
            <Separator />
            <Dl>
              <Dt>Ambush Chance:</Dt>
              <Dd>8%</Dd>
              <Dt>Ambush Resistance:</Dt>
              <Dd>2%</Dd>
              <Dt>Counter Chance:</Dt>
              <Dd>10%</Dd>
              <Dt>Counter Resistance:</Dt>
              <Dd>10%</Dd>
            </Dl>

            <h3 className="text-danube-500 dark:text-danube-700 mt-5">
              Elemental Atonement
            </h3>
            <Separator />
            <Dl>
              <Dt>Fire Atonement:</Dt>
              <Dd>24%</Dd>
              <Dt>Water Atonement:</Dt>
              <Dd>78%</Dd>
              <Dt>Ice Atonement:</Dt>
              <Dd>10%</Dd>
            </Dl>

            <h3 className="text-danube-500 dark:text-danube-700 mt-5">
              Rewards
            </h3>
            <Separator />
            <Dl>
              <Dt>XP:</Dt>
              <Dd>100</Dd>
              <Dt>Gold:</Dt>
              <Dd>500</Dd>
              <Dt>Shard Reward:</Dt>
              <Dd>1,000</Dd>
              <Dt>Drop Chance:</Dt>
              <Dd>10%</Dd>
            </Dl>
          </div>

          <div>
            <h3 className="text-danube-500 dark:text-danube-700">Core Stats</h3>
            <Separator />
            <Dl>
              <Dt>Str:</Dt>
              <Dd>100</Dd>
              <Dt>Dur:</Dt>
              <Dd>100</Dd>
              <Dt>Dex:</Dt>
              <Dd>100</Dd>
              <Dt>Int:</Dt>
              <Dd>100</Dd>
              <Dt>Chr:</Dt>
              <Dd>100</Dd>
              <Dt>Agi:</Dt>
              <Dd>100</Dd>
              <Dt>Focus:</Dt>
              <Dd>100</Dd>
            </Dl>

            <h3 className="text-danube-500 dark:text-danube-700 mt-5">
              Skills
            </h3>
            <Separator />
            <Dl>
              <Dt>Accuracy:</Dt>
              <Dd>1.5%</Dd>
              <Dt>Casting Accuracy:</Dt>
              <Dd>2.8%</Dd>
              <Dt>Dodge:</Dt>
              <Dd>10%</Dd>
              <Dt>Criticality:</Dt>
              <Dd>10%</Dd>
            </Dl>

            <h3 className="text-danube-500 dark:text-danube-700 mt-5">
              Resistances
            </h3>
            <Separator />
            <Dl>
              <Dt>Affix:</Dt>
              <Dd>1.5%</Dd>
              <Dt>Spells:</Dt>
              <Dd>2.8%</Dd>
              <Dt>Life Stealing:</Dt>
              <Dd>10%</Dd>
            </Dl>

            <h3 className="text-danube-500 dark:text-danube-700 mt-5">
              Devouring Light / Darkness
            </h3>
            <Separator />
            <Dl>
              <Dt>Devouring Light:</Dt>
              <Dd>1.5%</Dd>
              <Dt>Devouring Darkness:</Dt>
              <Dd>2.8%</Dd>
            </Dl>
          </div>
        </div>
      </Card>
    </ContainerWithTitle>
  );
};
