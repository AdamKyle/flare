import React, { ReactNode } from 'react';

import MonsterCombatSection from './monster-combat-section';
import MonsterIdentitySection from './monster-identity-section';
import MonsterQuestCelestialSection from './monster-quest-celestial-section';
import MonsterRaidSection from './monster-raid-section';
import MonsterSpellSection from './monster-spell-section';
import MonsterNormalTabPanelProps from '../types/monster-normal-tab-panel-props';

/**
 * The factual Normal tab panel: the Monster's persisted base sections with
 * no Gem/combat scaling applied.
 */
const MonsterNormalTabPanel = ({
  monster,
  navigation,
}: MonsterNormalTabPanelProps): ReactNode => (
  <div className="flex flex-col gap-6">
    <MonsterIdentitySection monster={monster} navigation={navigation} />
    <MonsterCombatSection monster={monster} navigation={navigation} />
    <MonsterSpellSection monster={monster} navigation={navigation} />
    <MonsterQuestCelestialSection monster={monster} navigation={navigation} />
    <MonsterRaidSection monster={monster} navigation={navigation} />
  </div>
);

export default MonsterNormalTabPanel;
