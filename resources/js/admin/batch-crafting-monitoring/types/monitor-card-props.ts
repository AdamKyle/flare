import React from 'react';

export default interface MonitorCardProps {
  children: React.ReactNode;
  onClick?: () => void;
  ariaLabel?: string;
}
