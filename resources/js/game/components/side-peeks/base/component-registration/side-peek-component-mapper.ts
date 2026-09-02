import React from 'react';

import { SidePeekComponentPropsMap } from './side-peek-component-props-map';
import { SidePeekComponentRegistrationEnum } from './side-peek-component-registration-enum';
import { SidePeekComponentRegistry } from './side-peek-component-registry';
import { SidePeekContentScrollMode } from '../enums/side-peek-content-scroll-mode';

export const resolveSidePeekComponent = <
  K extends keyof SidePeekComponentPropsMap,
>(
  key: K
): React.ComponentType<SidePeekComponentPropsMap[K]> =>
  SidePeekComponentRegistry[key].component;

export const resolveSidePeekContentScrollMode = (
  key: SidePeekComponentRegistrationEnum
): SidePeekContentScrollMode =>
  SidePeekComponentRegistry[key].content_scroll_mode ??
  SidePeekContentScrollMode.PARENT;
