import { ReactNode } from 'react';

type ComponentWithProps<P extends object> = (props: P) => ReactNode;

type PropsOf<C extends (props: object) => ReactNode> = C extends (
  props: infer P
) => ReactNode
  ? P
  : never;

type RequiredKeys<T extends object> = keyof T extends never
  ? never
  : { [K in keyof T]-?: undefined extends T[K] ? never : K }[keyof T];

export type TabItem<C extends (props: object) => ReactNode> =
  keyof PropsOf<C> extends never
    ? { label: string; component: C; props?: never }
    : RequiredKeys<PropsOf<C>> extends never
      ? { label: string; component: C; props?: PropsOf<C> }
      : { label: string; component: C; props: PropsOf<C> };

export type TabTuple<Cs extends readonly ((props: object) => ReactNode)[]> = {
  [I in keyof Cs]: TabItem<Cs[I]>;
};
