import React from 'react';

import DefinitionRow from './definition-row';
import InfoLabel from './info-label';
import TextRowProps from './types/text-row-props';

const TextRow = ({ label, value, skip = [] }: TextRowProps) => {
  if (value == null) {
    return null;
  }

  if (skip.length > 0 && skip.includes(value)) {
    return null;
  }

  return (
    <DefinitionRow
      left={<InfoLabel label={label} />}
      right={<span className="text-gray-800 dark:text-gray-200">{value}</span>}
    />
  );
};

export default TextRow;
