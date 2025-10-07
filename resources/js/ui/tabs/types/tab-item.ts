import React from 'react';

type PropsOf<C extends React.ComponentType<object>> = React.ComponentProps<C>;
type HasNoProps<T> = [keyof T] extends [never] ? true : false;
type HasRequiredProps<T> = [keyof Required<T>] extends [never] ? false : true;

export type TabItem<C extends React.ComponentType<object>> =
  HasNoProps<PropsOf<C>> extends true
    ? { label: string; component: C; props?: never }
    : HasRequiredProps<PropsOf<C>> extends true
      ? { label: string; component: C; props: PropsOf<C> }
      : { label: string; component: C; props?: PropsOf<C> };
