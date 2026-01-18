import type {
  ComponentProps,
  HTMLAttributes,
  ReactElement,
  ReactNode,
} from 'react';
import type {
  CartesianGridProps,
  ResponsiveContainerProps,
  XAxisProps,
  YAxisProps,
} from 'recharts';

import LineChartLineDefinition from 'ui/charts/line-chart/definitions/line-chart-line-definition';

type RechartsLineChartComponent = typeof import('recharts').LineChart;
type RechartsTooltipComponent = typeof import('recharts').Tooltip;

export default interface LineChartProps<
  TData extends object = Record<string, unknown>,
> {
  data: TData[];
  x_axis_data_key: keyof TData & string;
  lines: Array<LineChartLineDefinition<TData>>;
  tooltip_content: ReactElement;

  responsive_container_props: Omit<ResponsiveContainerProps, 'children'>;

  chart_props?: Omit<ComponentProps<RechartsLineChartComponent>, 'data'>;

  cartesian_grid_props?: CartesianGridProps;

  x_axis_props?: Omit<XAxisProps, 'dataKey'>;
  y_axis_props?: YAxisProps;

  tooltip_props?: Omit<ComponentProps<RechartsTooltipComponent>, 'content'>;

  outer_container_props?: HTMLAttributes<HTMLDivElement>;

  footer?: ReactNode;
  empty_state?: ReactNode;

  chart_children?: ReactNode;
}
