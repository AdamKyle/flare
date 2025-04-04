import React from "react";

import { ComponentForSidePeekPropsType } from "../types/component-for-side-peek-props-type";

export default interface UseManageSidePeekVisibilityDefinition<T> {
  isOpen: boolean;
  componentToRender: React.ComponentType<ComponentForSidePeekPropsType<T>>;
  componentProps: ComponentForSidePeekPropsType<T>;
  closeSidePeek: () => void;
}
