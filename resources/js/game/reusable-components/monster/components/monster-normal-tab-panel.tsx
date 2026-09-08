import React, { ReactNode } from 'react';

import MonsterCombatSection from './monster-combat-section';
import MonsterIdentitySection from './monster-identity-section';
import MonsterQuestCelestialSection from './monster-quest-celestial-section';
import MonsterRaidSection from './monster-raid-section';
import MonsterSpellSection from './monster-spell-section';
import MonsterNormalTabPanelProps from '../types/monster-normal-tab-panel-props';

import Separator from 'ui/separator/separator';

const MonsterNormalTabPanel = ({
  monster,
  navigation,
}: MonsterNormalTabPanelProps): ReactNode => {
  const sections: ReactNode[] = [
    <MonsterIdentitySection
      key="identity"
      monster={monster}
      navigation={navigation}
    />,
    <MonsterCombatSection
      key="combat"
      monster={monster}
      navigation={navigation}
    />,
    <MonsterSpellSection
      key="spells"
      monster={monster}
      navigation={navigation}
    />,
    <MonsterQuestCelestialSection
      key="quest-celestial"
      monster={monster}
      navigation={navigation}
    />,
    <MonsterRaidSection key="raid" monster={monster} navigation={navigation} />,
  ];

  return (
    <div className="grid grid-cols-1 gap-x-6 gap-y-4 lg:grid-cols-2">
      {sections.map((section, index) => (
        <React.Fragment key={index}>
          {index > 0 && <Separator additional_css="col-span-full my-0" />}
          {section}
        </React.Fragment>
      ))}
    </div>
  );
};

export default MonsterNormalTabPanel;
