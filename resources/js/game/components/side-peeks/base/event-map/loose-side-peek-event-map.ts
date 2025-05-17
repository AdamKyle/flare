import React from 'react';

import { SidePeekComponentMapper } from '../component-registration/side-peek-component-mapper';

type P = Record<string, unknown>;

export const LooseSidePeekMapper = SidePeekComponentMapper as unknown as {
  [K in keyof typeof SidePeekComponentMapper]: React.ComponentType<P>;
};
