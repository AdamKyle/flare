import type { LineProps } from 'recharts';

export default interface LineChartLineDefinition<TData extends object> {
  data_key: keyof TData & string;
  line_props: Omit<LineProps, 'dataKey'>;
}
