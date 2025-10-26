import StatToolTipProps from '../../item/tool-tips/types/stat-tool-tip-props';

export default interface InfoLabelProps {
  label: string;
  tooltip?: string;
  tooltipValue?: number;
  tooltipAlign?: StatToolTipProps['align'];
  tooltipRenderAsPercent?: boolean;
  tooltipSize?: StatToolTipProps['size'];
}
