import React, { ReactNode } from 'react';

import AttachedAffixesProps from './types/attached-affixes-props';
import BaseAttachedAffixesDetails from '../../../../../api-definitions/items/base-attached-affixes-details';
import { getStatAbbreviation } from '../../../enums/stat-types';

const AttachedAffixes = ({
  attached_affixes,
  stat_type,
}: AttachedAffixesProps): ReactNode => {
  return attached_affixes.map((attachedAffix: BaseAttachedAffixesDetails) => {
    const abbreviatedStat = getStatAbbreviation(stat_type);
    const modifierKey =
      `${abbreviatedStat}_mod` as keyof BaseAttachedAffixesDetails;

    const modifierValue = Number(attachedAffix[modifierKey] ?? 0);
    return (
      <li>
        <span className="text-slate-700 dark:text-slate-400">
          {attachedAffix.affix_name}
        </span>{' '}
        <span className="text-green-700 dark:text-green-500">
          (+
          {(modifierValue * 100).toFixed(2)}
          %);
        </span>
      </li>
    );
  });
};

export default AttachedAffixes;
