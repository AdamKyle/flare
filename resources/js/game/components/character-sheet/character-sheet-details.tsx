import { capitalize } from 'lodash';
import React, { ReactNode } from 'react';
import { match } from 'ts-pattern';

import { AttackTypes } from './enums/attack-types';
import { StatTypes } from './enums/stat-types';
import { useManageAttackDetailsBreakdown } from './hooks/use-manage-attack-details-breakdown';
import { useManageStatDetailsBreakdown } from './hooks/use-manage-stat-details-breakdown';
import Defence from './partials/character-attack-types/defence';
import Healing from './partials/character-attack-types/healing';
import Health from './partials/character-attack-types/health';
import RingDamage from './partials/character-attack-types/ring-damage';
import SpellDamage from './partials/character-attack-types/spell-damage';
import WeaponDamage from './partials/character-attack-types/weapon-damage';
import { CharacterStatTypeDetails } from './partials/character-stat-types/character-stat-type-details';
import CharacterSheetDetailsProps from './types/character-sheet-details-props';
import {
  formatNumberWithCommas,
  shortenNumber,
} from '../../util/format-number';
import XpBar from '../actions/components/character-details/xp-bar';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import ProgressButton from 'ui/buttons/button-progress';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

const CharacterSheetDetails = (
  props: CharacterSheetDetailsProps
): ReactNode => {
  const { openAttackDetails } = useManageAttackDetailsBreakdown();
  const { openStatDetails } = useManageStatDetailsBreakdown();

  const characterData = props.characterData;

  const characterInventorProgress =
    (characterData.inventory_count.data.inventory_count /
      characterData.inventory_count.data.inventory_max) *
    100;

  const renderAttackDetailsType = (attackType: AttackTypes): ReactNode => {
    return match(attackType)
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

  if (props.showAttackType && props.attackType !== null) {
    return renderAttackDetailsType(props.attackType);
  }

  if (props.showStatType && props.statType !== null) {
    return (
      <CharacterStatTypeDetails
        stat_type={props.statType}
        character_id={characterData.id}
      />
    );
  }

  return (
    <>
      <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4">
        <div>
          <Dl>
            <Dt>Character Name:</Dt>
            <Dd>{characterData.name}</Dd>
            <Dt>Race:</Dt>
            <Dd>{characterData.race}</Dd>
            <Dt>Class:</Dt>
            <Dd>{characterData.class}</Dd>
            <Dt>Level:</Dt>
            <Dd>
              {formatNumberWithCommas(characterData.level)} /{' '}
              {formatNumberWithCommas(characterData.max_level)}
            </Dd>
          </Dl>
        </div>
        <div>
          <Dl>
            <Dt>Gold:</Dt>
            <Dd>{formatNumberWithCommas(characterData.gold)}</Dd>
            <Dt>Gold Dust:</Dt>
            <Dd>{formatNumberWithCommas(characterData.gold_dust)}</Dd>
            <Dt>Shards:</Dt>
            <Dd>{formatNumberWithCommas(characterData.shards)}</Dd>
            <Dt>Copper Coins:</Dt>
            <Dd>{formatNumberWithCommas(characterData.copper_coins)}</Dd>
          </Dl>
        </div>
        <div>
          <Dl>
            <Dt>
              <LinkButton
                label={'Health:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openAttackDetails(AttackTypes.HEALTH)}
                aria_label={'Health Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <Dd>{formatNumberWithCommas(characterData.health)}</Dd>
            <Dt>
              <LinkButton
                label={'Weapon Damage:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openAttackDetails(AttackTypes.WEAPON)}
                aria_label={'Weapon Damage Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <Dd>{formatNumberWithCommas(characterData.weapon_attack)}</Dd>
            <Dt>
              <LinkButton
                label={'Healing Amount:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openAttackDetails(AttackTypes.HEALING)}
                aria_label={'Healing Amount Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <Dd>{formatNumberWithCommas(characterData.healing_amount)}</Dd>
            <Dt>
              <LinkButton
                label={'Spell Damage:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openAttackDetails(AttackTypes.SPELL_DAMAGE)}
                aria_label={'Spell Damage Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <Dd>{formatNumberWithCommas(characterData.spell_damage)}</Dd>
            <Dt>
              <LinkButton
                label={'Ring Damage:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openAttackDetails(AttackTypes.RING_DAMAGE)}
                aria_label={'Ring Damage Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <Dd>{formatNumberWithCommas(characterData.ring_damage)}</Dd>
            <Dt>
              <LinkButton
                label={'AC (Defence):'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openAttackDetails(AttackTypes.DEFENCE)}
                aria_label={'Armour Class (Defence) Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <Dd>{formatNumberWithCommas(characterData.ac)}</Dd>
          </Dl>
        </div>
        <div>
          <Dl>
            <Dt>To Hit Stat:</Dt>
            <Dd>{capitalize(characterData.to_hit_stat)}</Dd>
            <Dt>Class Bonus:</Dt>
            <Dd>{(characterData.class_bonus_chance * 100).toFixed(2)}%</Dd>
          </Dl>
        </div>
      </div>
      <Separator />
      <div className="w-full lg:w-3/4 xl:w-1/2 mx-auto my-6">
        <XpBar current_xp={characterData.xp} max_xp={characterData.xp_next} />
      </div>
      <Separator />
      <div className="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
        <div>
          <Dl>
            <Dt>Raw STR:</Dt>
            <Dd>{formatNumberWithCommas(characterData.str_raw)}</Dd>
            <Dt>Raw DEX:</Dt>
            <Dd>{formatNumberWithCommas(characterData.dex_raw)}</Dd>
            <Dt>Raw INT:</Dt>
            <Dd>{formatNumberWithCommas(characterData.int_raw)}</Dd>
            <Dt>Raw DUR:</Dt>
            <Dd>{formatNumberWithCommas(characterData.dur_raw)}</Dd>
            <Dt>Raw AGI:</Dt>
            <Dd>{formatNumberWithCommas(characterData.agi_raw)}</Dd>
            <Dt>Raw CHR:</Dt>
            <Dd>{formatNumberWithCommas(characterData.chr_raw)}</Dd>
            <Dt>Raw FOCUS:</Dt>
            <Dd>{formatNumberWithCommas(characterData.focus_raw)}</Dd>
          </Dl>
          <h3 className="text-danube-700 dark:text-danube-300 mt-5">
            Resistances & Reductions
          </h3>
          <Separator />
          <p className="my-2">
            Resistances and reductions help against stronger enemies, allowing
            for quicker take downs. Specific enchantments will raise these.
          </p>
          <Dl>
            <Dt>Spell Evasion:</Dt>
            <Dd>
              {(characterData.resistance_info.data.spell_evasion * 100).toFixed(
                2
              )}
              %
            </Dd>
            <Dt>Affix Damage Reduction:</Dt>
            <Dd>
              {(
                characterData.resistance_info.data.affix_damage_reduction * 100
              ).toFixed(2)}
              %
            </Dd>
            <Dt>Enemy Healing Reduction:</Dt>
            <Dd>
              {(
                characterData.resistance_info.data.healing_reduction * 100
              ).toFixed(2)}
            </Dd>
          </Dl>
        </div>
        <div>
          <Dl>
            <Dt>
              <LinkButton
                label={'Modded STR:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openStatDetails(StatTypes.STR)}
                aria_label={'Modded Str Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <Dd>{shortenNumber(characterData.str_modded)}</Dd>
            <Dt>
              <LinkButton
                label={'Modded DEX:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openStatDetails(StatTypes.DEX)}
                aria_label={'Modded Dex Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <Dd>{shortenNumber(characterData.dex_modded)}</Dd>
            <Dt>
              <LinkButton
                label={'Modded INT:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openStatDetails(StatTypes.INT)}
                aria_label={'Modded Int Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <Dd>{shortenNumber(characterData.int_modded)}</Dd>
            <Dt>
              <LinkButton
                label={'Modded DUR:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openStatDetails(StatTypes.DUR)}
                aria_label={'Modded Dur Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <Dd>{shortenNumber(characterData.dur_modded)}</Dd>
            <Dt>
              <LinkButton
                label={'Modded AGI:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openStatDetails(StatTypes.AGI)}
                aria_label={'Modded Agi Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <Dd>{shortenNumber(characterData.agi_modded)}</Dd>
            <Dt>
              <LinkButton
                label={'Modded CHR:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openStatDetails(StatTypes.CHR)}
                aria_label={'Modded Chr Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <Dd>{shortenNumber(characterData.chr_modded)}</Dd>
            <Dt>
              <LinkButton
                label={'Modded FOCUS:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openStatDetails(StatTypes.FOCUS)}
                aria_label={'Modded Focus Link'}
                additional_css={'font-bold'}
              />
            </Dt>
            <Dd>{shortenNumber(characterData.focus_modded)}</Dd>
          </Dl>
          <h3 className="text-danube-700 dark:text-danube-300 mt-5">
            Elemental Atonement
          </h3>
          <Separator />
          <p className="my-2">
            Gems boost elemental power, aiding in battles against stronger
            enemies, including raid bosses and weekly events.
          </p>
          <Dl>
            <Dt>Fire:</Dt>
            <Dd>{characterData.elemental_atonements.atonements.fire * 100}%</Dd>
            <Dt>Ice:</Dt>
            <Dd>{characterData.elemental_atonements.atonements.ice * 100}%</Dd>
            <Dt>Water:</Dt>
            <Dd>
              {characterData.elemental_atonements.atonements.water * 100}%
            </Dd>
          </Dl>
        </div>
        <div>
          <ProgressButton
            progress={characterInventorProgress}
            on_click={props.openCharacterInventory}
            label={`Manage Inventory (${characterData.inventory_count.data.inventory_count}/${characterData.inventory_count.data.inventory_max})`}
            variant={ButtonVariant.PRIMARY}
            additional_css="w-full my-2"
          />
          <Button
            on_click={props.openReincarnationSystem}
            label="Manage Reincarnation"
            variant={ButtonVariant.SUCCESS}
            additional_css="w-full my-2"
          />
          <Button
            on_click={props.openClassRanksSystem}
            label="Manage Class Ranks"
            variant={ButtonVariant.SUCCESS}
            additional_css="w-full my-2"
          />
        </div>
      </div>
    </>
  );
};

export default CharacterSheetDetails;
