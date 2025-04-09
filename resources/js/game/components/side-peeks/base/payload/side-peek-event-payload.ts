import {SidePeekComponentPropsMap} from "../component-registration/side-peek-component-props-map";

// SidePeekEventPayload is a union type that maps each SidePeekComponentRegistrationEnum key
// to a tuple containing:
// 1. The component enum key (e.g. "BACKPACK")
// 2. The required props for that specific component (from SidePeekComponentPropsMap)
//
// This allows us to strongly type the event payload so that when we emit a side peek event,
// TypeScript enforces that the correct props are provided for the selected component.

export type SidePeekEventPayload = {
  [K in keyof SidePeekComponentPropsMap]: [K, SidePeekComponentPropsMap[K]];
}[keyof SidePeekComponentPropsMap];

