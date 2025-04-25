import React from 'react';

export default interface ModalProps {
  is_open: boolean;
  title: string;
  on_close?: () => void;
  allow_clicking_outside?: boolean;
  children?: React.ReactNode;
}
