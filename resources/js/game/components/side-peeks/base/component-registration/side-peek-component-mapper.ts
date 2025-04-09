import React from 'react';

import {SidePeekComponentRegistry} from "./side-peek-component-registery";

export const SidePeekComponentMapper = Object.fromEntries(
  Object.entries(SidePeekComponentRegistry).map(([key, { component }]) => [key, component])
) as {
  [K in keyof typeof SidePeekComponentRegistry]: React.ComponentType<
    typeof SidePeekComponentRegistry[K]['props']
  >;
};