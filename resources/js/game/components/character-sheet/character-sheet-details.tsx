import { capitalize } from 'lodash';
import React, { ReactNode } from 'react';

import CharacterSheetDetailsProps from './types/character-sheet-details-props';
import {
  formatNumberWithCommas,
  shortenNumber,
} from '../../util/format-number';
import XpBar from '../actions/components/character-details/xp-bar';

import Button from 'ui/buttons/button';
import ProgressButton from 'ui/buttons/button-progress';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Separator from 'ui/seperatror/separator';

const CharacterSheetDetails = (
  props: CharacterSheetDetailsProps
): ReactNode => {
  const characterData = props.characterData;

  const characterInventorProgress =
    (characterData.inventory_count.inventory_count /
      characterData.inventory_count.inventory_max) *
    100;

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
            <dt className="font-bold">Level:</dt>
            <dd>
              {formatNumberWithCommas(characterData.level)} /{' '}
              {formatNumberWithCommas(characterData.max_level)}
            </dd>
            <dt className="font-bold">Health:</dt>
            <dd>{formatNumberWithCommas(characterData.health)}</dd>
            <dt className="font-bold">Total Attack:</dt>
            <dd>{formatNumberWithCommas(characterData.attack)}</dd>
            <dt className="font-bold">Total Healing:</dt>
            <dd>{formatNumberWithCommas(characterData.healing)}</dd>
            <dt className="font-bold">AC (Defence):</dt>
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
            <dt className="font-bold">Modded STR:</dt>
            <dd>{shortenNumber(characterData.str_modded)}</dd>
            <dt className="font-bold">Modded DEX:</dt>
            <dd>{shortenNumber(characterData.dex_modded)}</dd>
            <dt className="font-bold">Modded INT:</dt>
            <dd>{shortenNumber(characterData.int_modded)}</dd>
            <dt className="font-bold">Modded DUR:</dt>
            <dd>{shortenNumber(characterData.dur_modded)}</dd>
            <dt className="font-bold">Modded AGI:</dt>
            <dd>{shortenNumber(characterData.agi_modded)}</dd>
            <dt className="font-bold">Modded CHR:</dt>
            <dd>{shortenNumber(characterData.chr_modded)}</dd>
            <dt className="font-bold">Modded FOCUS:</dt>
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
