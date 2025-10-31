import { ReactNode } from 'react';

export type ComponentFromProps<P extends object> = (props: P) => ReactNode;

type RequiredKeys<T extends object> = keyof T extends never
  ? never
  : { [K in keyof T]-?: undefined extends T[K] ? never : K }[keyof T];

export type TabItemFromProps<P extends object> = keyof P extends never
  ? {
      label: string;
      component: ComponentFromProps<P>;
      activity_icon?: string;
      icon_styles?: string;
      props?: never;
    }
  : RequiredKeys<P> extends never
    ? {
        label: string;
        component: ComponentFromProps<P>;
        activity_icon?: string;
        icon_styles?: string;
        props?: P;
      }
    : {
        label: string;
        component: ComponentFromProps<P>;
        activity_icon?: string;
        icon_styles?: string;
        props: P;
      };

export type TabTupleFromProps<PTuple extends readonly object[]> = {
  [I in keyof PTuple]: TabItemFromProps<PTuple[I]>;
};
