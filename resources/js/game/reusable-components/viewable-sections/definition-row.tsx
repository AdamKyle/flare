import React from 'react';

import DefinitionRowProps from './types/definition-row-props';

import Dd from 'ui/dl/dd';
import Dt from 'ui/dl/dt';

const DefinitionRow = ({ left, right }: DefinitionRowProps) => {
  return (
    <>
      <Dt>{left}</Dt>
      <Dd>{right}</Dd>
    </>
  );
};

export default DefinitionRow;
