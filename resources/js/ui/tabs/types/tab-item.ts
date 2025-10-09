import { ReactNode } from 'react';

/** A React component described by its props type. */
export type ComponentFromProps<P extends object> = (props: P) => ReactNode;

/** Keys in T that are required (not optional/undefined). */
type RequiredKeys<T extends object> = keyof T extends never
  ? never
  : { [K in keyof T]-?: undefined extends T[K] ? never : K }[keyof T];

/**
 * A single tab definition derived from a component's props.
 * - If the component has no props -> props is forbidden.
 * - If the component has only optional props -> props is optional.
 * - If the component has required props -> props is required.
 */
export type TabItemFromProps<P extends object> = keyof P extends never
  ? { label: string; component: ComponentFromProps<P>; props?: never }
  : RequiredKeys<P> extends never
    ? { label: string; component: ComponentFromProps<P>; props?: P }
    : { label: string; component: ComponentFromProps<P>; props: P };

/** A tuple of tab items, one per props type in PTuple. */
export type TabTupleFromProps<PTuple extends readonly object[]> = {
  [I in keyof PTuple]: TabItemFromProps<PTuple[I]>;
};
