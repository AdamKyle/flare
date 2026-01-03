import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { isNil } from 'lodash';
import React, { ReactNode } from 'react';
import { match, P } from 'ts-pattern';

import CharacterAttackTypeBreakdownProps from './types/character-attack-type-breakdown-props';
import { AttackTypes } from '../../enums/attack-types';
import { useGetCharacterAttackDetails } from './api/hooks/use-get-character-attack-details';
import Defence from './attack-type-sections/defence';
import Healing from './attack-type-sections/healing';
import Health from './attack-type-sections/health';
import RingDamage from './attack-type-sections/ring-damage';
import SpellDamage from './attack-type-sections/spell-damage';
import WeaponDamage from './attack-type-sections/weapon-damage';

import { GameDataError } from 'game-data/components/game-data-error';
import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const CharacterAttackTypeBreakdown = ({
  attack_type,
  close_attack_details,
}: CharacterAttackTypeBreakdownProps): ReactNode => {
  const { gameData } = useGameData();
  const { data, loading, error } = useGetCharacterAttackDetails({
    character_id: gameData?.character?.id || 0,
    attack_type,
  });

  const characterData = gameData?.character;

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

  if (loading) {
    return (
      <ContainerWithTitle
        manageSectionVisibility={renderManageSectionVisibility()}
        title={'One Moment while we fetch the details'}
      >
        <Card>
          <InfiniteLoader />
        </Card>
      </ContainerWithTitle>
    );
  }

  console.log(data, characterData);

  if (!characterData || !data) {
    return (
      <ContainerWithTitle
        manageSectionVisibility={renderManageSectionVisibility()}
        title={'Woah! What happened here?'}
      >
        <Card>
          <GameDataError />
        </Card>
      </ContainerWithTitle>
    );
  }

  if (error) {
    return (
      <ContainerWithTitle
        manageSectionVisibility={renderManageSectionVisibility()}
        title={'Woah! Something blew up'}
      >
        <Card>
          <ApiErrorAlert apiError={error.message} />;
        </Card>
      </ContainerWithTitle>
    );
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

  const renderAttackDetailsType = (attack_type: AttackTypes): ReactNode => {
    return match(attack_type)
      .with(AttackTypes.WEAPON, () => <WeaponDamage break_down={data} />)
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

  if (isNil(attack_type)) {
    return null;
  }

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
