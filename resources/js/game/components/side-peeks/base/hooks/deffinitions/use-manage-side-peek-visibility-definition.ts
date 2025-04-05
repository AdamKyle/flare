import React from 'react';

import { ComponentForSidePeekPropsType } from '../types/component-for-side-peek-props-type';

export default interface UseManageSidePeekVisibilityDefinition<T> {
  ComponentToRender: React.ComponentType<ComponentForSidePeekPropsType<T>> | null;
  componentProps: ComponentForSidePeekPropsType<T>;
  closeSidePeek: () => void;
}
