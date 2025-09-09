import React from 'react';

import DefinitionRow from '../../../../../reusable-components/viewable-sections/definition-row';
import InfoLabel from '../../../../../reusable-components/viewable-sections/info-label';
import Section from '../../../../../reusable-components/viewable-sections/section';
import { formatSignedPercent } from '../../../../../util/format-number';
import AppliedHolyStacksSectionProps from '../types/attached-holy-stacks-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dl from 'ui/dl/dl';
import Separator from 'ui/separator/separator';

const AppliedHolyStacksSection = ({
  stacks,
  on_close,
}: AppliedHolyStacksSectionProps) => {
  if (!stacks.length) {
    return null;
  }

  const renderStatIncreaseRow = (value: number) => {
    return (
      <DefinitionRow
        left={
          <InfoLabel
            label="Stat Increase"
            tooltip={`This increases all stats on the item by ${formatSignedPercent(
              value
            ).replace(
              '^\\+',
              ''
            )} and stacks with all other holy stacks on this item.`}
            tooltipValue={value}
            tooltipAlign="right"
            tooltipRenderAsPercent
            tooltipSize="sm"
          />
        }
        right={
          <span className="inline-flex items-center gap-2 whitespace-nowrap">
            <i
              className="fas fa-chevron-up text-emerald-600"
              aria-hidden="true"
            />
            <span className="font-semibold text-emerald-700 tabular-nums">
              {formatSignedPercent(value)}
            </span>
          </span>
        }
      />
    );
  };

  const renderDevouringLightRow = (value: number) => {
    return (
      <DefinitionRow
        left={
          <InfoLabel
            label="Devouring Light"
            tooltip={
              "This stacks additively and reduces the enemy's chance to void you, which would render you weak and vulnerable. (If an enemy voids you, all enchantments on your gear fail.)"
            }
            tooltipValue={value}
            tooltipAlign="right"
            tooltipRenderAsPercent
            tooltipSize="sm"
          />
        }
        right={
          <span className="inline-flex items-center gap-2 whitespace-nowrap">
            <i
              className="fas fa-chevron-up text-emerald-600"
              aria-hidden="true"
            />
            <span className="font-semibold text-emerald-700 tabular-nums">
              {formatSignedPercent(value)}
            </span>
          </span>
        }
      />
    );
  };

  return (
    <div className="p-4">
      <div className="text-center">
        <Button
          on_click={on_close}
          label="Close"
          variant={ButtonVariant.SUCCESS}
        />
      </div>

      <div>
        <h2 className="text-lg my-2 text-gray-800 dark:text-gray-300">
          Attached Holy Stacks
        </h2>
        <Separator />
        <p className="my-4 text-gray-800 dark:text-gray-300">
          These stacks are applied via various oils that you can craft with
          Alchemy. All stacks are summed together and applied to either the
          items stats or the total devouring light bonus of your character.
        </p>
        <Separator />
      </div>

      <Section title="Applied Holy Stacks">
        <div className="space-y-4 mt-2">
          {stacks.map((stack) => (
            <div
              key={stack.id}
              className="border border-gray-200 dark:border-gray-700 rounded-md p-4"
            >
              <Dl>
                {renderStatIncreaseRow(Number(stack.stat_increase_bonus))}
                {renderDevouringLightRow(
                  Number(stack.devouring_darkness_bonus)
                )}
              </Dl>
            </div>
          ))}
        </div>
      </Section>
    </div>
  );
};

export default AppliedHolyStacksSection;
