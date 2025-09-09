import React from 'react';

import Dd from 'ui/dl/dd';
import Dt from 'ui/dl/dt';

type DefinitionRowProps = {
  left: React.ReactNode;
  right: React.ReactNode;
};

const DefinitionRow = ({ left, right }: DefinitionRowProps) => {
  return (
    <>
      <Dt>{left}</Dt>
      <Dd>{right}</Dd>
    </>
  );
};

export default DefinitionRow;
