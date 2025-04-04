import React from "react";

import {ComponentForSidePeekPropsType} from "./component-for-side-peek-props-type";

export type ComponentForSidePeekType<T> = React.ComponentType<ComponentForSidePeekPropsType<T>>;
