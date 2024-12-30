import React, { ReactNode } from 'react';

import CharacterSheetDetailsProps from './types/character-sheet-details-props';
import XpBar from '../actions/components/character-details/xp-bar';

import Button from 'ui/buttons/button';
import ProgressButton from 'ui/buttons/button-progress';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Separator from 'ui/seperatror/separator';

const CharacterSheetDetails = (
  props: CharacterSheetDetailsProps
): ReactNode => {
  return (
    <>
      <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4">
        <div>
          <dl>
            <dt className="font-bold">Character Name:</dt>
            <dd>Sample Name</dd>
            <dt className="font-bold">Race:</dt>
            <dd>Sample Race</dd>
            <dt className="font-bold">Class:</dt>
            <dd>Sample Class</dd>
          </dl>
        </div>
        <div>
          <dl>
            <dt className="font-bold">Gold:</dt>
            <dd>2,000,000,000,000</dd>
            <dt className="font-bold">Gold Dust:</dt>
            <dd>1,000,000</dd>
            <dt className="font-bold">Shards:</dt>
            <dd>1,000,000</dd>
            <dt className="font-bold">Copper Coins:</dt>
            <dd>1,000,000</dd>
          </dl>
        </div>
        <div>
          <dl>
            <dt className="font-bold">Level:</dt>
            <dd>1,000 / 5,000</dd>
            <dt className="font-bold">Health:</dt>
            <dd>2,000</dd>
            <dt className="font-bold">Total Attack:</dt>
            <dd>2,000</dd>
            <dt className="font-bold">Total Healing:</dt>
            <dd>2,000</dd>
            <dt className="font-bold">AC (Defence):</dt>
            <dd>500</dd>
          </dl>
        </div>
        <div>
          <dl>
            <dt className="font-bold">To Hit Stat:</dt>
            <dd>Dex</dd>
            <dt className="font-bold">Class Bonus:</dt>
            <dd>50%</dd>
          </dl>
        </div>
      </div>
      <Separator />
      <div className="w-full lg:w-3/4 xl:w-1/2 mx-auto my-6">
        <XpBar current_xp={1000} max_xp={10000} />
      </div>
      <Separator />
      <div className="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
        <div>
          <dl>
            <dt className="font-bold">Raw STR:</dt>
            <dd>150,000,000,000</dd>
            <dt className="font-bold">Raw DEX:</dt>
            <dd>150,000,000,000</dd>
            <dt className="font-bold">Raw INT:</dt>
            <dd>150,000,000,000</dd>
            <dt className="font-bold">Raw DUR:</dt>
            <dd>150,000,000,000</dd>
            <dt className="font-bold">Raw AGI:</dt>
            <dd>150,000,000,000</dd>
            <dt className="font-bold">Raw CHR:</dt>
            <dd>150,000,000,000</dd>
            <dt className="font-bold">Raw FOCUS:</dt>
            <dd>150,000,000,000</dd>
          </dl>
          <h3 className="text-danube-500 dark:text-danube-700 mt-5">
            Resistances & Reductions
          </h3>
          <Separator />
          <p className="my-2">
            Resistances and reductions help against stronger enemies, allowing
            for quicker takedowns. Specific enchantments will raise these.
          </p>
          <dl>
            <dt className="font-bold">Spell Evasion:</dt>
            <dd>75%</dd>
            <dt className="font-bold">Affix Damage Reduction:</dt>
            <dd>75%</dd>
            <dt className="font-bold">Enemy Healing Reduction:</dt>
            <dd>75%</dd>
          </dl>
        </div>
        <div>
          <dl>
            <dt className="font-bold">Modded STR:</dt>
            <dd>150,000,000,000</dd>
            <dt className="font-bold">Modded DEX:</dt>
            <dd>150,000,000,000</dd>
            <dt className="font-bold">Modded INT:</dt>
            <dd>150,000,000,000</dd>
            <dt className="font-bold">Modded DUR:</dt>
            <dd>150,000,000,000</dd>
            <dt className="font-bold">Modded AGI:</dt>
            <dd>150,000,000,000</dd>
            <dt className="font-bold">Modded CHR:</dt>
            <dd>150,000,000,000</dd>
            <dt className="font-bold">Modded FOCUS:</dt>
            <dd>150,000,000,000</dd>
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
            <dd>75%</dd>
            <dt className="font-bold">Ice:</dt>
            <dd>75%</dd>
            <dt className="font-bold">Water:</dt>
            <dd>75%</dd>
          </dl>
        </div>
        <div>
          <ProgressButton
            progress={10}
            on_click={props.openCharacterInventory}
            label="Manage Inventory (56/75)"
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
