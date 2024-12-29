import React from 'react';

export default interface ContainerProps {
  children: React.ReactNode;
  manageSectionVisibility: () => void;
  title: string;
}
