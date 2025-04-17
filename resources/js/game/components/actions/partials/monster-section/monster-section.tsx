import React, { ReactNode, useState } from 'react';

import MonsterSectionProps from './types/monster-section-props';
import AttackButtonsContainer from '../../components/fight-section/attack-buttons-container';
import AttackMessages from '../../components/fight-section/attack-messages';
import AttackMessageDefinition from '../../components/fight-section/deffinitions/attack-message-definition';
import { AttackMessageType } from '../../components/fight-section/enums/attack-message-type';
import { HealthBarType } from '../../components/fight-section/enums/health-bar-type';
import HealthBar from '../../components/fight-section/health-bar';
import HealthBarContainer from '../../components/fight-section/health-bar-container';
import MonsterTopSection from '../../components/fight-section/monster-top-section';

import Button from 'ui/buttons/button';
import { ButtonGradientVarient } from 'ui/buttons/enums/button-gradient-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import GradientButton from 'ui/buttons/gradient-button';
import Separator from 'ui/seperatror/separator';

const MonsterSection = (props: MonsterSectionProps): ReactNode => {
  const [, setNextAction] = useState<number>(0);
  const [, setPrevAction] = useState<number>(0);

  const messages: AttackMessageDefinition[] = [
    {
      message: 'You Attack for 150,000 Damage!',
      type: AttackMessageType.PLAYER_ATTACK,
    },
    {
      message: 'Your spells charge and magic crackles in the air!',
      type: AttackMessageType.REGULAR,
    },
    {
      message: 'The enemy stops your spells and attack you for 125,000 Damage',
      type: AttackMessageType.ENEMY_ATTACK,
    },
  ];

  return (
    <>
      <MonsterTopSection
        img_src="https://placecats.com/250/250"
        next_action={() => (currentIndex: number) => {
          setNextAction(currentIndex);
        }}
        prev_action={() => (currentIndex: number) => {
          setPrevAction(currentIndex);
        }}
        view_monster_stats={props.show_monster_stats}
        monster_name="Sewer Rat"
      />
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
      <div className="block xl:hidden">
        <Separator additional_css="w-full lg:w-1/5 mx-auto my-6" />
        <AttackMessages messages={messages} />
      </div>
    </>
  );
};

export default MonsterSection;
