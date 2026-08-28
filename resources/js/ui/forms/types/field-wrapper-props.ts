import { ReactNode } from 'react';

export default interface FieldWrapperProps {
  id: string;
  label: string;
  required?: boolean;
  description?: string;
  error?: string | null;
  children: (described_by: string | undefined) => ReactNode;
}
