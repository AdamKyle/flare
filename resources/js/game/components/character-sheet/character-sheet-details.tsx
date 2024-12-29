import React, { ReactNode } from 'react';

import CharacterSheetDetailsProps from './types/character-sheet-details-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Separator from 'ui/seperatror/separator';

const CharacterSheetDetails = (
  props: CharacterSheetDetailsProps
): ReactNode => {
  return (
    <>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <dl>
            <dt>Character Name:</dt>
            <dd>Sample Name</dd>
            <dt>Race:</dt>
            <dd>Sample Name</dd>
            <dt>Class:</dt>
            <dd>Sample Name</dd>
          </dl>
        </div>

        <div>
          <dl>
            <dt>Gold:</dt>
            <dd>2,000,000,000,000</dd>
            <dt>Gold Dust:</dt>
            <dd>1,000,000</dd>
            <dt>Shards:</dt>
            <dd>1,000,000</dd>
            <dt>Copper Coins:</dt>
            <dd>1,000,000</dd>
          </dl>
        </div>

        <div>
          <dl>
            <dt>Level:</dt>
            <dd>1,000</dd>
            <dt>Health:</dt>
            <dd>2,000</dd>
            <dt>Total Attack:</dt>
            <dd>2,000</dd>
            <dt>Total Healing:</dt>
            <dd>2,000</dd>
            <dt>AC (Defence):</dt>
            <dd>500</dd>
          </dl>
        </div>
      </div>
      <Separator />
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <dl>
            <dt>Raw STR:</dt>
            <dd>150,000,000,000</dd>
            <dt>Raw DEX:</dt>
            <dd>150,000,000,000</dd>
            <dt>Raw INT:</dt>
            <dd>150,000,000,000</dd>
            <dt>Raw DUR:</dt>
            <dd>150,000,000,000</dd>
            <dt>Raw AGI:</dt>
            <dd>150,000,000,000</dd>
            <dt>Raw CHR:</dt>
            <dd>150,000,000,000</dd>
            <dt>Raw FOCUS:</dt>
            <dd>150,000,000,000</dd>
          </dl>

          <h3 className="text-danube-500 dark:text-danube-700 mt-5">
            Resistances & Reductions
          </h3>
          <Separator />

          <p className={'my-2 mb-2'}>
            Resistances and reductions help against stronger enemies, to help
            you quickly take them down. Specific enchantments will help raise
            these.
          </p>

          <dl>
            <dt>Spell Evasion:</dt>
            <dd>75%</dd>
            <dt>Affix Damage Reduction:</dt>
            <dd>75%</dd>
            <dt>Enemy Healing Reduction:</dt>
            <dd>75%</dd>
          </dl>

          <h3 className="text-danube-500 dark:text-danube-700 mt-5">
            Resurrection Chance
          </h3>
          <Separator />

          <dl>
            <dt>Chance:</dt>
            <dd>75%</dd>
          </dl>
        </div>

        <div>
          <dl>
            <dt>Modded STR:</dt>
            <dd>150,000,000,000</dd>
            <dt>Modded DEX:</dt>
            <dd>150,000,000,000</dd>
            <dt>Modded INT:</dt>
            <dd>150,000,000,000</dd>
            <dt>Modded DUR:</dt>
            <dd>150,000,000,000</dd>
            <dt>Modded AGI:</dt>
            <dd>150,000,000,000</dd>
            <dt>Modded CHR:</dt>
            <dd>150,000,000,000</dd>
            <dt>Modded FOCUS:</dt>
            <dd>150,000,000,000</dd>
          </dl>

          <h3 className="text-danube-500 dark:text-danube-700 mt-5">
            Elemental Atonement
          </h3>
          <Separator />

          <p className={'my-2 mb-2'}>
            Gems boost elemental power, aiding in battles against stronger
            enemies, including raid bosses and weekly events.
          </p>

          <dl>
            <dt>Fire:</dt>
            <dd>75%</dd>
            <dt>Ice:</dt>
            <dd>75%</dd>
            <dt>Water:</dt>
            <dd>75%</dd>
          </dl>

          <h3 className="text-danube-500 dark:text-danube-700 mt-5">
            Elemental Damage
          </h3>
          <Separator />

          <dl>
            <dt>Fire:</dt>
            <dd>75%</dd>
          </dl>
        </div>
        <div>
          <div>
            <Button
              on_click={() => props.openCharacterInventory()}
              label={'Manage Inventory'}
              variant={ButtonVariant.PRIMARY}
              additional_css={'w-full my-2'}
            />
          </div>
          <div>
            <Button
              on_click={() => props.openReincarnationSystem()}
              label={'Manage Reincarnation'}
              variant={ButtonVariant.SUCCESS}
              additional_css={'w-full my-2'}
            />
          </div>

          <div>
            <Button
              on_click={() => props.openClassRanksSystem()}
              label={'Manage Class Ranks'}
              variant={ButtonVariant.SUCCESS}
              additional_css={'w-full my-2'}
            />
          </div>
        </div>
      </div>
    </>
  );
};

export default CharacterSheetDetails;
