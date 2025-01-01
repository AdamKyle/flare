import React, { ReactNode } from 'react';
import { match, P } from 'ts-pattern';

import CharacterAttackTypeBreakdownProps from './types/character-attack-type-breakdown-props';
import { AttackTypes } from '../character-sheet/enums/attack-types';
import Defence from '../character-sheet/partials/character-attack-types/defence';
import Healing from '../character-sheet/partials/character-attack-types/healing';
import Health from '../character-sheet/partials/character-attack-types/health';
import RingDamage from '../character-sheet/partials/character-attack-types/ring-damage';
import SpellDamage from '../character-sheet/partials/character-attack-types/spell-damage';
import WeaponDamage from '../character-sheet/partials/character-attack-types/weapon-damage';

import { GameDataError } from 'game-data/components/game-data-error';
import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';

const CharacterAttackTypeBreakdown = ({
  attack_type,
  close_attack_details,
}: CharacterAttackTypeBreakdownProps): ReactNode => {
  const { gameData } = useGameData();

  const characterData = gameData?.character;

  if (!characterData) {
    return <GameDataError />;
  }

  const renderTitle = (): string => {
    return match({
      attack_type,
    })
      .with(
        { attack_type: AttackTypes.WEAPON },
        () => `${characterData.name} Weapon Attack Details`
      )
      .with(
        { attack_type: AttackTypes.SPELL_DAMAGE },
        () => `${characterData.name} Spell Damage Details`
      )
      .with(
        { attack_type: AttackTypes.HEALING },
        () => `${characterData.name} Healing Details`
      )
      .with(
        { attack_type: AttackTypes.RING_DAMAGE },
        () => `${characterData.name} Ring Damage`
      )
      .with(
        { attack_type: AttackTypes.HEALTH },
        () => `${characterData.name} Health Breakdown`
      )
      .with(
        { attack_type: AttackTypes.DEFENCE },
        () => `${characterData.name} Defence Breakdown`
      )
      .otherwise(() => `${characterData.name}`);
  };

  const renderManageSectionVisibility = (): (() => void) => {
    return match({
      attack_type,
    })
      .with(
        {
          attack_type: P.union(
            AttackTypes.WEAPON,
            AttackTypes.SPELL_DAMAGE,
            AttackTypes.HEALING,
            AttackTypes.RING_DAMAGE,
            AttackTypes.HEALTH,
            AttackTypes.DEFENCE
          ),
        },
        () => close_attack_details
      )
      .otherwise(() => () => {});
  };

  const renderAttackDetailsType = (attack_type: AttackTypes): ReactNode => {
    return match(attack_type)
      .with(AttackTypes.WEAPON, () => <WeaponDamage />)
      .with(AttackTypes.SPELL_DAMAGE, () => <SpellDamage />)
      .with(AttackTypes.HEALING, () => <Healing />)
      .with(AttackTypes.RING_DAMAGE, () => <RingDamage />)
      .with(AttackTypes.HEALTH, () => <Health />)
      .with(AttackTypes.DEFENCE, () => <Defence />)
      .otherwise(() => (
        <Alert variant={AlertVariant.DANGER}>
          <p>
            Invalid component returned. This is a bug. Please head to discord:
            Top Right Profile icon, CLick discord and report this in #bugs.
          </p>
        </Alert>
      ));
  };

  return (
    <ContainerWithTitle
      manageSectionVisibility={renderManageSectionVisibility()}
      title={renderTitle()}
    >
      <Card>{renderAttackDetailsType(attack_type)}</Card>
    </ContainerWithTitle>
  );
};

export default CharacterAttackTypeBreakdown;
