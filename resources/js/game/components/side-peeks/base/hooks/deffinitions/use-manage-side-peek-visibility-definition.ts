import React from 'react';

import { SidePeekComponentPropsMap } from '../../component-registration/side-peek-component-props-map';
import { SidePeekComponentRegistrationEnum } from '../../component-registration/side-peek-component-registration-enum';

export type AllSidePeekProps =
  SidePeekComponentPropsMap[keyof SidePeekComponentPropsMap];

export default interface UseManageSidePeekVisibilityDefinition {
  componentKey: SidePeekComponentRegistrationEnum | null;
  ComponentToRender: React.ComponentType<Record<string, unknown>> | null;
  componentProps: AllSidePeekProps;
  closeSidePeek: () => void;
}
