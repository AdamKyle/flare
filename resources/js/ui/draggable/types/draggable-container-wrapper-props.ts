import React from 'react';

export default interface DraggableContainerWrapperProps {
  children: React.ReactNode;
  additional_css: string;
  zoom?: number;
  center_on_x: number;
  center_on_y: number;
}
