import React from 'react';

export default interface ContainerProps {
  children: React.ReactNode;
  manageSectionVisibility: () => void;
  title: string;
  close_button_data_guide_target?: string;
}
