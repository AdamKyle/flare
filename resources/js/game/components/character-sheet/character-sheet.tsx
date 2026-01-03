import React, { ReactNode } from 'react';
import { match, P } from 'ts-pattern';

import CharacterClassRanks from './character-class-ranks';
import CharacterInventoryManagement from './character-inventory-management';
import CharacterReincarnation from './character-reincarnation';
import CharacterSheetDetails from './character-sheet-details';
import { AttackTypes } from './enums/attack-types';
import { useAttackDetailsVisibility } from './hooks/use-attack-details-visibility';
import { useManageCharacterInventoryVisibility } from './hooks/use-manage-character-inventory-visibility';
import { useManageClassRanksVisibility } from './hooks/use-manage-class-ranks-visibility';
import { useManageReincarnationVisibility } from './hooks/use-manage-reincarnation-visibility';
import CharacterSheetProps from './types/character-sheet-props';

import { GameDataError } from 'game-data/components/game-data-error';
import { useGameData } from 'game-data/hooks/use-game-data';

import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';

const CharacterSheet = (props: CharacterSheetProps): ReactNode => {
  const { showReincarnation, openReincarnation, closeReincarnation } =
    useManageReincarnationVisibility();
  const { showClassRanks, openClassRanks, closeClassRanks } =
    useManageClassRanksVisibility();
  const { showInventory, openInventory, closeInventory } =
    useManageCharacterInventoryVisibility();
  const { showAttackType, attackType, closeAttackDetails } =
    useAttackDetailsVisibility();

  const { gameData } = useGameData();

  const characterData = gameData?.character;

  if (!characterData) {
    return (
      <ContainerWithTitle
        manageSectionVisibility={props.manageCharacterSheetVisibility}
        title={'An error occurred'}
      >
        <Card>
          <GameDataError />
        </Card>
      </ContainerWithTitle>
    );
  }

  const renderCharacterSheetScreen = (): ReactNode => {
    return match({ showReincarnation, showClassRanks, showInventory })
      .with({ showReincarnation: true }, () => (
        <CharacterReincarnation
          reincarnation_info={characterData.reincarnation_info.data}
        />
      ))
      .with({ showClassRanks: true }, () => <CharacterClassRanks />)
      .with({ showInventory: true }, () => (
        <CharacterInventoryManagement character_id={characterData.id} />
      ))
      .otherwise(() => (
        <CharacterSheetDetails
          openReincarnationSystem={openReincarnation}
          openClassRanksSystem={openClassRanks}
          openCharacterInventory={openInventory}
          characterData={characterData}
          showAttackType={showAttackType}
          attackType={attackType}
        />
      ));
  };

  const renderTitle = (): string => {
    return match({
      showReincarnation,
      showClassRanks,
      showInventory,
      showAttackType,
      attackType,
    })
      .with(
        { showReincarnation: true },
        () => `${characterData.name} Reincarnation`
      )
      .with({ showClassRanks: true }, () => `${characterData.name} Class Ranks`)
      .with({ showInventory: true }, () => `${characterData.name} Inventory`)
      .with(
        { showAttackType: true, attackType: AttackTypes.WEAPON },
        () => `${characterData.name}: Weapon Attack Details`
      )
      .with(
        { showAttackType: true, attackType: AttackTypes.SPELL_DAMAGE },
        () => `${characterData.name}: Spell Damage Details`
      )
      .with(
        { showAttackType: true, attackType: AttackTypes.HEALING },
        () => `${characterData.name}: Healing Details`
      )
      .with(
        { showAttackType: true, attackType: AttackTypes.RING_DAMAGE },
        () => `${characterData.name}: Ring Damage`
      )
      .with(
        { showAttackType: true, attackType: AttackTypes.HEALTH },
        () => `${characterData.name}: Health Breakdown`
      )
      .with(
        { showAttackType, attackType: AttackTypes.DEFENCE },
        () => `${characterData.name}: Defence Breakdown`
      )
      .otherwise(() => `${characterData.name}`);
  };

  const renderManageSectionVisibility = (): (() => void) => {
    return match({
      showReincarnation,
      showClassRanks,
      showInventory,
      showAttackType,
      attackType,
    })
      .with({ showReincarnation: true }, () => closeReincarnation)
      .with({ showClassRanks: true }, () => closeClassRanks)
      .with({ showInventory: true }, () => closeInventory)
      .with(
        {
          showAttackType: true,
          attackType: P.union(
            AttackTypes.WEAPON,
            AttackTypes.SPELL_DAMAGE,
            AttackTypes.HEALING,
            AttackTypes.RING_DAMAGE,
            AttackTypes.HEALTH,
            AttackTypes.DEFENCE
          ),
        },
        () => closeAttackDetails
      )
      .otherwise(() => props.manageCharacterSheetVisibility);
  };

  return (
    <ContainerWithTitle
      manageSectionVisibility={renderManageSectionVisibility()}
      title={renderTitle()}
    >
      <Card>{renderCharacterSheetScreen()}</Card>
    </ContainerWithTitle>
  );
};

export default CharacterSheet;
