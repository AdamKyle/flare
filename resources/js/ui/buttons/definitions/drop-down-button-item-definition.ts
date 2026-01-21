import { ReactNode } from 'react';

export default interface DropDownItemDefinition<T> {
  label: string;
  value: T;
  aria_label: string;
  icon?: ReactNode;
  class_name?: string;
}
