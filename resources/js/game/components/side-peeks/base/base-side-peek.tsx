import React from 'react';

import {useDynamicComponentVisibility} from "./hooks/use-manage-side-peek-visibility";

import SidePeek from "ui/side-peek/side-peek";

const BaseSidePeek = () => {
  const {ComponentToRender, componentProps, closeSidePeek} = useDynamicComponentVisibility<Record<string, unknown>>()

  return (<SidePeek is_open={componentProps.is_open} on_close={closeSidePeek} allow_clicking_outside={componentProps.allow_clicking_outside}>
    <ComponentToRender {...componentProps} />
  </SidePeek>);
}

export default BaseSidePeek;