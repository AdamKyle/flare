import React from 'react';

import { SidePeekComponentPropsMap } from '../../component-registration/side-peek-component-props-map';
import { SidePeekComponentRegistrationEnum } from '../../component-registration/side-peek-component-registration-enum';

export type AllSidePeekProps =
  SidePeekComponentPropsMap[keyof SidePeekComponentPropsMap];

export default interface UseManageSidePeekVisibilityDefinition {
  componentKey: SidePeekComponentRegistrationEnum | null;
  ComponentToRender: React.ComponentType<AllSidePeekProps> | null;
  componentProps: AllSidePeekProps;
  closeSidePeek: () => void;
}
