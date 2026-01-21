import React from 'react';

import MoveToSetProps from './types/move-to-set-props';
import SetChoices from '../../../sets/set-choices';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import Separator from 'ui/separator/separator';

const MoveToSet = ({ character_id }: MoveToSetProps) => {
  return (
    <>
      <div className="prose dark:prose-invert">
        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
          Move To Set - Rules
        </h2>

        <p className="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-300">
          You can move any item to any set from your inventory, but if you plan
          to equip that set you must follow the rules below.
        </p>

        <ul className="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-300">
          <li className="flex gap-2">
            <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400 dark:bg-gray-500" />
            <span>
              <span className="font-semibold text-gray-900 dark:text-gray-100">
                Hands:
              </span>{' '}
              1 or 2 weapons for hands, or 1 or 2 shields or 1 duel wielded
              weapon (bow, hammer or stave).
            </span>
          </li>

          <li className="flex gap-2">
            <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400 dark:bg-gray-500" />
            <span>
              <span className="font-semibold text-gray-900 dark:text-gray-100">
                Armour:
              </span>{' '}
              1 of each type, body, head, leggings, sleeves, gloves and feet
            </span>
          </li>

          <li className="flex gap-2">
            <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400 dark:bg-gray-500" />
            <span>
              <span className="font-semibold text-gray-900 dark:text-gray-100">
                Spells:
              </span>{' '}
              Max of 2 regardless of type.
            </span>
          </li>

          <li className="flex gap-2">
            <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400 dark:bg-gray-500" />
            <span>
              <span className="font-semibold text-gray-900 dark:text-gray-100">
                Rings:
              </span>{' '}
              Max of 2
            </span>
          </li>

          <li className="flex gap-2">
            <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400 dark:bg-gray-500" />
            <span>
              <span className="font-semibold text-gray-900 dark:text-gray-100">
                Trinkets:
              </span>{' '}
              Max of 1
            </span>
          </li>

          <li className="flex gap-2">
            <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400 dark:bg-gray-500" />
            <span>
              <span className="font-semibold text-gray-900 dark:text-gray-100">
                Uniques (green items):
              </span>{' '}
              Max of 1 item regardless of type. Ie, if you have a unique helmet,
              you cannot have a unique ring as well.<sup>*</sup>
            </span>
          </li>

          <li className="flex gap-2">
            <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400 dark:bg-gray-500" />
            <span>
              <span className="font-semibold text-gray-900 dark:text-gray-100">
                Mythics (orange items):
              </span>{' '}
              Max of 1 item regardless of type. Ie, if you have a mythic helmet,
              you cannot have a mythic ring as well.<sup>*</sup>
            </span>
          </li>

          <li className="flex gap-2">
            <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400 dark:bg-gray-500" />
            <span>
              <span className="font-semibold text-gray-900 dark:text-gray-100">
                Comsic (light purple items):
              </span>{' '}
              Max of 1 item regardless of type. Ie, if you have a cosmic helmet,
              you cannot have a cosmic ring as well.<sup>*</sup>
            </span>
          </li>

          <li className="flex gap-2">
            <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400 dark:bg-gray-500" />
            <span>
              <span className="font-semibold text-gray-900 dark:text-gray-100">
                Ancestral Items (purple items):
              </span>{' '}
              1 Ancestral item only.
            </span>
          </li>
        </ul>

        <p className="mt-3 text-sm leading-6 text-gray-700 dark:text-gray-300">
          <sup>*</sup>
          <strong>Note:</strong> A Set may only have one unique OR one mythic OR
          one cosmic. You may not have one of each type.
        </p>

        <p className="mt-3 text-sm leading-6 text-gray-700 dark:text-gray-300">
          The above rules only apply to characters who want to equip the set,
          You may also use a set as a stash tab with unlimited items.
        </p>
      </div>
      <Separator />
      <div>
        <SetChoices
          character_id={character_id}
          on_set_change={(selectedSet: DropdownItem) => {
            console.log(selectedSet);
          }}
          on_set_selection_clear={() => {}}
        />
      </div>
    </>
  );
};

export default MoveToSet;
