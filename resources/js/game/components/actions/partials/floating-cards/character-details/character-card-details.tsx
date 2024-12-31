import React, { ReactNode } from 'react';

import { useManageCharacterInventoryVisibility } from './hooks/use-manage-character-inventory-visibility';
import CharacterCardDetailsProps from './types/character-card-details-props';
import { shortenNumber } from '../../../../../util/format-number';
import { AttackTypes } from '../../../../character-sheet/enums/attack-types';
import { useManageAttackDetailsBreakdown } from '../../../../character-sheet/hooks/use-manage-attack-details-breakdown';
import { useManageCharacterSheetVisibility } from '../../../../hooks/use-manage-character-sheet-visibility';
import XpBar from '../../../components/character-details/xp-bar';

import Button from 'ui/buttons/button';
import ProgressButton from 'ui/buttons/button-progress';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';
import Separator from 'ui/seperatror/separator';

const CharacterCardDetails = ({
  characterData,
}: CharacterCardDetailsProps): ReactNode => {
  const { openCharacterSheet } = useManageCharacterSheetVisibility();
  const { openCharacterInventory } = useManageCharacterInventoryVisibility();
  const { openAttackDetails } = useManageAttackDetailsBreakdown();

  const characterInventorProgress =
    (characterData.inventory_count.inventory_count /
      characterData.inventory_count.inventory_max) *
    100;

  return (
    <>
      <XpBar current_xp={characterData.xp} max_xp={characterData.xp_next} />
      <div className="grid grid-cols-2 gap-2">
        <div>
          <h4 className="text-danube-500 dark:text-danube-700">Stats</h4>
          <Separator />
          <dl className="text-gray-600 dark:text-gray-700">
            <dt className="font-bold">Str:</dt>
            <dd>{shortenNumber(characterData.str_modded)}</dd>
            <dt className="font-bold">Dex:</dt>
            <dd>{shortenNumber(characterData.dex_modded)}</dd>
            <dt className="font-bold">Int:</dt>
            <dd>{shortenNumber(characterData.int_modded)}</dd>
            <dt className="font-bold">Dur:</dt>
            <dd>{shortenNumber(characterData.dur_modded)}</dd>
            <dt className="font-bold">Agi:</dt>
            <dd>{shortenNumber(characterData.agi_modded)}</dd>
            <dt className="font-bold">Chr:</dt>
            <dd>{shortenNumber(characterData.chr_modded)}</dd>
            <dt className="font-bold">Focus:</dt>
            <dd>{shortenNumber(characterData.focus_modded)}</dd>
          </dl>
        </div>
        <div>
          <h4 className="text-danube-500 dark:text-danube-700">Attack Stats</h4>
          <Separator />
          <dl className="text-gray-600 dark:text-gray-700">
            <dt className="font-bold">HP:</dt>
            <dd>{shortenNumber(characterData.health)}</dd>
            <dt className="font-bold">AC (Defence):</dt>
            <dd>{shortenNumber(characterData.ac)}</dd>
            <dt className="font-bold">
              <LinkButton
                label={'Weapon:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openAttackDetails(AttackTypes.WEAPON)}
                aria_label={'Weapon Damage Link'}
                additional_css={'font-bold'}
              />
            </dt>
            <dd>{shortenNumber(characterData.weapon_attack)}</dd>
            <dt className="font-bold">
              <LinkButton
                label={'Healing:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openAttackDetails(AttackTypes.HEALING)}
                aria_label={'Healing Amount Link'}
                additional_css={'font-bold'}
              />
            </dt>
            <dd>{shortenNumber(characterData.healing_amount)}</dd>
            <dt className="font-bold">
              <LinkButton
                label={'Spell:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openAttackDetails(AttackTypes.SPELL_DAMAGE)}
                aria_label={'Spell Damage Link'}
                additional_css={'font-bold'}
              />
            </dt>
            <dd>{shortenNumber(characterData.spell_damage)}</dd>
            <dt className="font-bold">
              {' '}
              <LinkButton
                label={'Ring:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openAttackDetails(AttackTypes.RING_DAMAGE)}
                aria_label={'Ring Damage Link'}
                additional_css={'font-bold'}
              />
            </dt>
            <dd>{shortenNumber(characterData.ring_damage)}</dd>
          </dl>
        </div>
      </div>
      <div className="my-4">
        <h4 className="text-danube-500 dark:text-danube-700">Currencies</h4>
        <Separator />
        <dl className="text-gray-600 dark:text-gray-700">
          <dt className="font-bold">Gold:</dt>
          <dd>{shortenNumber(characterData.gold)}</dd>
          <dt className="font-bold">Gold Dust:</dt>
          <dd>{shortenNumber(characterData.gold_dust)}</dd>
          <dt className="font-bold">Shards:</dt>
          <dd>{shortenNumber(characterData.shards)}</dd>
          <dt className="font-bold">Copper Coins:</dt>
          <dd>{shortenNumber(characterData.copper_coins)}</dd>
        </dl>
      </div>
      <Separator />
      <ProgressButton
        progress={characterInventorProgress}
        on_click={() => openCharacterInventory()}
        label={`Manage Inventory (${characterData.inventory_count.inventory_count}/${characterData.inventory_count.inventory_max})`}
        variant={ButtonVariant.SUCCESS}
        additional_css="w-full my-2"
      />
      <Button
        label="See More Details"
        on_click={() => {
          openCharacterSheet();
        }}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full"
      />
    </>
  );
};

export default CharacterCardDetails;
