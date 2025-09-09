import StatInfoToolTipProps from '../../../../../../../reusable-components/item/types/stat-info-tool-tip-props';

export default interface InfoLabelProps {
  label: string;
  tooltip?: string;
  tooltipValue?: number;
  tooltipAlign?: StatInfoToolTipProps['align'];
  tooltipRenderAsPercent?: boolean;
  tooltipSize?: StatInfoToolTipProps['size'];
}
