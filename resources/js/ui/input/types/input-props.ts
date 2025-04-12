import React from 'react';

export default interface InputProps {
  on_change: (e: React.ChangeEvent<HTMLInputElement>) => void;
  value?: string;
}
