import { ModalComponentPropsMap } from '../component-registration/modal-component-props';

// ModalEventPayload is a union type that maps each ModalComponentRegistrationEnum key
// to a tuple containing:
// 1. The component enum key (e.g. "CONFIRM_DELETE")
// 2. The required props for that specific component (from ModalComponentPropsMap)
//
// This allows us to strongly type the event payload so that when we emit a modal event,
// TypeScript enforces that the correct props are provided for the selected component.

export type ModalEventPayload = {
  [K in keyof ModalComponentPropsMap]: [K, ModalComponentPropsMap[K]];
}[keyof ModalComponentPropsMap];
