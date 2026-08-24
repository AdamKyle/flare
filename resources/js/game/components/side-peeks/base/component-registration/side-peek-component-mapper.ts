import React from 'react';

import { SidePeekComponentPropsMap } from './side-peek-component-props-map';
import { SidePeekComponentRegistry } from './side-peek-component-registry';

export const resolveSidePeekComponent = <
  K extends keyof SidePeekComponentPropsMap,
>(
  key: K
): React.ComponentType<SidePeekComponentPropsMap[K]> =>
  SidePeekComponentRegistry[key].component;
