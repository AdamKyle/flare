import React from 'react';

import { ModalComponentRegistry } from './modal-component-registry';

export const ModalComponentMapper = Object.fromEntries(
  Object.entries(ModalComponentRegistry).map(([key, { component }]) => [
    key,
    component,
  ])
) as {
  [K in keyof typeof ModalComponentRegistry]: React.ComponentType<
    (typeof ModalComponentRegistry)[K]['props']
  >;
};
