import React from "react";

import { SidePeekComponentPropsMap } from '../../component-registration/side-peek-component-props-map';

export type AllSidePeekProps = SidePeekComponentPropsMap[keyof SidePeekComponentPropsMap];

export default interface UseManageSidePeekVisibilityDefinition {
  ComponentToRender: React.ComponentType<AllSidePeekProps> | null;
  componentProps: AllSidePeekProps;
  closeSidePeek: () => void;
}