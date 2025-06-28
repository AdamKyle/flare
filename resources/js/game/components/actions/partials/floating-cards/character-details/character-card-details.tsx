import React, { ReactNode } from 'react';

import { useManageCharacterInventoryVisibility } from './hooks/use-manage-character-inventory-visibility';
import CharacterCardDetailsProps from './types/character-card-details-props';
import { shortenNumber } from '../../../../../util/format-number';
import { AttackTypes } from '../../../../character-sheet/enums/attack-types';
import { StatTypes } from '../../../../character-sheet/enums/stat-types';
import { useManageAttackDetailsBreakdown } from '../../../../character-sheet/hooks/use-manage-attack-details-breakdown';
import { useManageStatDetailsBreakdown } from '../../../../character-sheet/hooks/use-manage-stat-details-breakdown';
import { useManageCharacterSheetVisibility } from '../../../../hooks/use-manage-character-sheet-visibility';
import XpBar from '../../../components/character-details/xp-bar';

import Button from 'ui/buttons/button';
import ProgressButton from 'ui/buttons/button-progress';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/seperatror/separator';

const CharacterCardDetails = ({
  characterData,
}: CharacterCardDetailsProps): ReactNode => {
  const { openCharacterSheet } = useManageCharacterSheetVisibility();
  const { openCharacterInventory } = useManageCharacterInventoryVisibility();
  const { openAttackDetails } = useManageAttackDetailsBreakdown();
  const { openStatDetails } = useManageStatDetailsBreakdown();

  const rawProgress =
    (characterData.inventory_count.data.inventory_count /
      characterData.inventory_count.data.inventory_max) *
    100;

  const characterInventorProgress = Math.min(rawProgress, 100);

  return (
    <>
      <XpBar current_xp={characterData.xp} max_xp={characterData.xp_next} />
      <div className="grid grid-cols-2 gap-2">
        <div>
          <h4 className="text-danube-500 dark:text-danube-700">Stats</h4>
          <Separator />
          <Dl>
            <Dt>
              <LinkButton
                label={'Str:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openStatDetails(StatTypes.STR)}
                aria_label={'Str Breakdown Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <Dd>{shortenNumber(characterData.str_modded)}</Dd>
            <Dt>
              <LinkButton
                label={'Dex:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openStatDetails(StatTypes.DEX)}
                aria_label={'Dex Breakdown Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <Dd>{shortenNumber(characterData.dex_modded)}</Dd>
            <Dt>
              <LinkButton
                label={'Int:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openStatDetails(StatTypes.INT)}
                aria_label={'Int Breakdown Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <Dd>{shortenNumber(characterData.int_modded)}</Dd>
            <Dt>
              <LinkButton
                label={'Dur:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openStatDetails(StatTypes.DUR)}
                aria_label={'Dur Breakdown Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <Dd>{shortenNumber(characterData.dur_modded)}</Dd>
            <Dt>
              <LinkButton
                label={'Agi:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openStatDetails(StatTypes.AGI)}
                aria_label={'Agi Breakdown Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <Dd>{shortenNumber(characterData.agi_modded)}</Dd>
            <Dt>
              <LinkButton
                label={'Chr:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openStatDetails(StatTypes.CHR)}
                aria_label={'Chr Breakdown Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <Dd>{shortenNumber(characterData.chr_modded)}</Dd>
            <Dt>
              <LinkButton
                label={'Focus:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openStatDetails(StatTypes.FOCUS)}
                aria_label={'Focus Breakdown Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <dd>{shortenNumber(characterData.focus_modded)}</dd>
          </Dl>
        </div>
        <div>
          <h4 className="text-danube-500 dark:text-danube-700">Attack Stats</h4>
          <Separator />
          <Dl>
            <Dt>
              <LinkButton
                label={'HP:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openAttackDetails(AttackTypes.HEALTH)}
                aria_label={'HP Breakdown Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <Dd>{shortenNumber(characterData.health)}</Dd>
            <Dt>
              <LinkButton
                label={'AC (Defence):'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openAttackDetails(AttackTypes.DEFENCE)}
                aria_label={'Ac (Defence) Breakdown Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <Dd>{shortenNumber(characterData.ac)}</Dd>
            <Dt>
              <LinkButton
                label={'Weapon:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openAttackDetails(AttackTypes.WEAPON)}
                aria_label={'Weapon Damage Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <Dd>{shortenNumber(characterData.weapon_attack)}</Dd>
            <Dt>
              <LinkButton
                label={'Healing:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openAttackDetails(AttackTypes.HEALING)}
                aria_label={'Healing Amount Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <Dd>{shortenNumber(characterData.healing_amount)}</Dd>
            <Dt>
              <LinkButton
                label={'Spell:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openAttackDetails(AttackTypes.SPELL_DAMAGE)}
                aria_label={'Spell Damage Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <Dd>{shortenNumber(characterData.spell_damage)}</Dd>
            <Dt>
              {' '}
              <LinkButton
                label={'Ring:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openAttackDetails(AttackTypes.RING_DAMAGE)}
                aria_label={'Ring Damage Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <Dd>{shortenNumber(characterData.ring_damage)}</Dd>
          </Dl>
        </div>
      </div>
      <div className="my-4">
        <h4 className="text-danube-500 dark:text-danube-700">Currencies</h4>
        <Separator />
        <Dl>
          <Dt>Gold:</Dt>
          <Dd>{shortenNumber(characterData.gold)}</Dd>
          <Dt>Gold Dust:</Dt>
          <Dd>{shortenNumber(characterData.gold_dust)}</Dd>
          <Dt>Shards:</Dt>
          <Dd>{shortenNumber(characterData.shards)}</Dd>
          <Dt>Copper Coins:</Dt>
          <Dd>{shortenNumber(characterData.copper_coins)}</Dd>
        </Dl>
      </div>
      <Separator />
      <ProgressButton
        progress={characterInventorProgress}
        on_click={() => openCharacterInventory()}
        label={`Manage Inventory (${characterData.inventory_count.data.inventory_count}/${characterData.inventory_count.data.inventory_max})`}
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
