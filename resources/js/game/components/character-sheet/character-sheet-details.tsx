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
import Separator from 'ui/seperatror/separator';

const CharacterSheetDetails = (
  props: CharacterSheetDetailsProps
): ReactNode => {
  const { openAttackDetails } = useManageAttackDetailsBreakdown();
  const { openStatDetails } = useManageStatDetailsBreakdown();

  const characterData = props.characterData;

  const characterInventorProgress =
    (characterData.inventory_count.inventory_count /
      characterData.inventory_count.inventory_max) *
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
    return <CharacterStatTypeDetails stat_type={props.statType} />;
  }

  return (
    <>
      <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4">
        <div>
          <dl>
            <dt className="font-bold">Character Name:</dt>
            <dd>{characterData.name}</dd>
            <dt className="font-bold">Race:</dt>
            <dd>{characterData.race}</dd>
            <dt className="font-bold">Class:</dt>
            <dd>{characterData.class}</dd>
            <dt className="font-bold">Level:</dt>
            <dd>
              {formatNumberWithCommas(characterData.level)} /{' '}
              {formatNumberWithCommas(characterData.max_level)}
            </dd>
          </dl>
        </div>
        <div>
          <dl>
            <dt className="font-bold">Gold:</dt>
            <dd>{formatNumberWithCommas(characterData.gold)}</dd>
            <dt className="font-bold">Gold Dust:</dt>
            <dd>{formatNumberWithCommas(characterData.gold_dust)}</dd>
            <dt className="font-bold">Shards:</dt>
            <dd>{formatNumberWithCommas(characterData.shards)}</dd>
            <dt className="font-bold">Copper Coins:</dt>
            <dd>{formatNumberWithCommas(characterData.copper_coins)}</dd>
          </dl>
        </div>
        <div>
          <dl>
            <dt className="font-bold">
              <LinkButton
                label={'Health:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openAttackDetails(AttackTypes.HEALTH)}
                aria_label={'Health Link'}
                additional_css={'font-bold'}
              />
            </dt>
            <dd>{formatNumberWithCommas(characterData.health)}</dd>
            <dt className="font-bold">
              <LinkButton
                label={'Weapon Damage:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openAttackDetails(AttackTypes.WEAPON)}
                aria_label={'Weapon Damage Link'}
                additional_css={'font-bold'}
              />
            </dt>
            <dd>{formatNumberWithCommas(characterData.weapon_attack)}</dd>
            <dt className="font-bold">
              <LinkButton
                label={'Healing Amount:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openAttackDetails(AttackTypes.HEALING)}
                aria_label={'Healing Amount Link'}
                additional_css={'font-bold'}
              />
            </dt>
            <dd>{formatNumberWithCommas(characterData.healing_amount)}</dd>
            <dt className="font-bold">
              <LinkButton
                label={'Spell Damage:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openAttackDetails(AttackTypes.SPELL_DAMAGE)}
                aria_label={'Spell Damage Link'}
                additional_css={'font-bold'}
              />
            </dt>
            <dd>{formatNumberWithCommas(characterData.spell_damage)}</dd>
            <dt className="font-bold">
              <LinkButton
                label={'Ring Damage:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openAttackDetails(AttackTypes.RING_DAMAGE)}
                aria_label={'Ring Damage Link'}
                additional_css={'font-bold'}
              />
            </dt>
            <dd>{formatNumberWithCommas(characterData.ring_damage)}</dd>
            <dt className="font-bold">
              <LinkButton
                label={'AC (Defence):'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openAttackDetails(AttackTypes.DEFENCE)}
                aria_label={'Armour Class (Defence) Link'}
                additional_css={'font-bold'}
              />
            </dt>
            <dd>{formatNumberWithCommas(characterData.ac)}</dd>
          </dl>
        </div>
        <div>
          <dl>
            <dt className="font-bold">To Hit Stat:</dt>
            <dd>{capitalize(characterData.to_hit_stat)}</dd>
            <dt className="font-bold">Class Bonus:</dt>
            <dd>{(characterData.class_bonus_chance * 100).toFixed(2)}%</dd>
          </dl>
        </div>
      </div>
      <Separator />
      <div className="w-full lg:w-3/4 xl:w-1/2 mx-auto my-6">
        <XpBar current_xp={characterData.xp} max_xp={characterData.xp_next} />
      </div>
      <Separator />
      <div className="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
        <div>
          <dl>
            <dt className="font-bold">Raw STR:</dt>
            <dd>{formatNumberWithCommas(characterData.str_raw)}</dd>
            <dt className="font-bold">Raw DEX:</dt>
            <dd>{formatNumberWithCommas(characterData.dex_raw)}</dd>
            <dt className="font-bold">Raw INT:</dt>
            <dd>{formatNumberWithCommas(characterData.int_raw)}</dd>
            <dt className="font-bold">Raw DUR:</dt>
            <dd>{formatNumberWithCommas(characterData.dur_raw)}</dd>
            <dt className="font-bold">Raw AGI:</dt>
            <dd>{formatNumberWithCommas(characterData.agi_raw)}</dd>
            <dt className="font-bold">Raw CHR:</dt>
            <dd>{formatNumberWithCommas(characterData.chr_raw)}</dd>
            <dt className="font-bold">Raw FOCUS:</dt>
            <dd>{formatNumberWithCommas(characterData.focus_raw)}</dd>
          </dl>
          <h3 className="text-danube-500 dark:text-danube-700 mt-5">
            Resistances & Reductions
          </h3>
          <Separator />
          <p className="my-2">
            Resistances and reductions help against stronger enemies, allowing
            for quicker take downs. Specific enchantments will raise these.
          </p>
          <dl>
            <dt className="font-bold">Spell Evasion:</dt>
            <dd>
              {(characterData.resistance_info.spell_evasion * 100).toFixed(2)}%
            </dd>
            <dt className="font-bold">Affix Damage Reduction:</dt>
            <dd>
              {(
                characterData.resistance_info.affix_damage_reduction * 100
              ).toFixed(2)}
              %
            </dd>
            <dt className="font-bold">Enemy Healing Reduction:</dt>
            <dd>
              {(characterData.resistance_info.healing_reduction * 100).toFixed(
                2
              )}
            </dd>
          </dl>
        </div>
        <div>
          <dl>
            <dt className="font-bold">
              <LinkButton
                label={'Modded STR:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openStatDetails(StatTypes.STR)}
                aria_label={'Modded Str Link'}
                additional_css={'font-bold'}
              />
            </dt>
            <dd>{shortenNumber(characterData.str_modded)}</dd>
            <dt className="font-bold">
              <LinkButton
                label={'Modded DEX:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openStatDetails(StatTypes.DEX)}
                aria_label={'Modded Dex Link'}
                additional_css={'font-bold'}
              />
            </dt>
            <dd>{shortenNumber(characterData.dex_modded)}</dd>
            <dt className="font-bold">
              <LinkButton
                label={'Modded INT:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openStatDetails(StatTypes.INT)}
                aria_label={'Modded Int Link'}
                additional_css={'font-bold'}
              />
            </dt>
            <dd>{shortenNumber(characterData.int_modded)}</dd>
            <dt className="font-bold">
              <LinkButton
                label={'Modded DUR:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openStatDetails(StatTypes.DUR)}
                aria_label={'Modded Dur Link'}
                additional_css={'font-bold'}
              />
            </dt>
            <dd>{shortenNumber(characterData.dur_modded)}</dd>
            <dt className="font-bold">
              <LinkButton
                label={'Modded AGI:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openStatDetails(StatTypes.AGI)}
                aria_label={'Modded Agi Link'}
                additional_css={'font-bold'}
              />
            </dt>
            <dd>{shortenNumber(characterData.agi_modded)}</dd>
            <dt className="font-bold">
              <LinkButton
                label={'Modded CHR:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openStatDetails(StatTypes.CHR)}
                aria_label={'Modded Chr Link'}
                additional_css={'font-bold'}
              />
            </dt>
            <dd>{shortenNumber(characterData.chr_modded)}</dd>
            <dt className="font-bold">
              <LinkButton
                label={'Modded FOCUS:'}
                variant={ButtonVariant.PRIMARY}
                on_click={() => openStatDetails(StatTypes.FOCUS)}
                aria_label={'Modded Focus Link'}
                additional_css={'font-bold'}
              />
            </dt>
            <dd>{shortenNumber(characterData.focus_modded)}</dd>
          </dl>
          <h3 className="text-danube-500 dark:text-danube-700 mt-5">
            Elemental Atonement
          </h3>
          <Separator />
          <p className="my-2">
            Gems boost elemental power, aiding in battles against stronger
            enemies, including raid bosses and weekly events.
          </p>
          <dl>
            <dt className="font-bold">Fire:</dt>
            <dd>{characterData.elemental_atonements.atonements.fire * 100}%</dd>
            <dt className="font-bold">Ice:</dt>
            <dd>{characterData.elemental_atonements.atonements.ice * 100}%</dd>
            <dt className="font-bold">Water:</dt>
            <dd>
              {characterData.elemental_atonements.atonements.water * 100}%
            </dd>
          </dl>
        </div>
        <div>
          <ProgressButton
            progress={characterInventorProgress}
            on_click={props.openCharacterInventory}
            label={`Manage Inventory (${characterData.inventory_count.inventory_count}/${characterData.inventory_count.inventory_max})`}
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
