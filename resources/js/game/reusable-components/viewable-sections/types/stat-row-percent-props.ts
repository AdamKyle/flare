export default interface StatRowPercentProps {
  label: string;
  value: number;
  tooltip?: string;
  tooltipValue?: number;
  tooltipAlign?: 'left' | 'right';
  tooltipRenderAsPercent?: boolean;
  tooltipSize?: 'sm' | 'md';
  negative?: boolean;
}
