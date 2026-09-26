import { ReactNode } from 'react';

export type ComponentFromProps<P extends object> = (props: P) => ReactNode;

export interface TabItemPresentation {
  label: string;
  activity_icon?: string;
  icon_styles?: string;
}

export interface TabItemWithOptionalProps<
  P extends object,
> extends TabItemPresentation {
  component: ComponentFromProps<P>;
  props?: P;
}

export interface TabItemWithRequiredProps<
  P extends object,
> extends TabItemPresentation {
  component: ComponentFromProps<P>;
  props: P;
}

type RequiredKeys<T extends object> = keyof T extends never
  ? never
  : { [K in keyof T]-?: object extends Pick<T, K> ? never : K }[keyof T];

export type TabItemFromProps<P extends object> =
  RequiredKeys<P> extends never
    ? TabItemWithOptionalProps<P>
    : TabItemWithRequiredProps<P>;

export type TabTupleFromProps<PTuple extends readonly object[]> = {
  [I in keyof PTuple]: TabItemFromProps<PTuple[I]>;
};
