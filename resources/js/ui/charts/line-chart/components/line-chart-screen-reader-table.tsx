import React, { useMemo } from 'react';

import LineChartScreenReaderTableProps from '../types/line-chart-screen-reader-table-props';
import { resolveLineChartNumber } from '../utils/resolve-line-chart-number';

const UNAVAILABLE_VALUE_LABEL = 'Unavailable';

interface ScreenReaderRow {
  key: string;
  x_value: string;
  series_values: Array<{ data_key: string; value: string }>;
}

const buildRows = <TData extends object>({
  data,
  x_data_key,
  x_formatter,
  lines,
}: LineChartScreenReaderTableProps<TData>): ScreenReaderRow[] => {
  return data.map((point, index) => {
    const xValue = point[x_data_key];
    const resolvedXValue = resolveLineChartNumber(xValue);

    const seriesValues = lines.map((line) => {
      const resolvedValue = resolveLineChartNumber(point[line.data_key]);
      const formattedValue =
        resolvedValue === null
          ? UNAVAILABLE_VALUE_LABEL
          : (line.value_formatter?.(resolvedValue) ??
            resolvedValue.toLocaleString());

      return { data_key: line.data_key, value: formattedValue };
    });

    return {
      key: `${index}-${String(xValue)}`,
      x_value:
        resolvedXValue === null
          ? UNAVAILABLE_VALUE_LABEL
          : x_formatter(resolvedXValue),
      series_values: seriesValues,
    };
  });
};

const LineChartScreenReaderTable = <TData extends object>(
  props: LineChartScreenReaderTableProps<TData>
) => {
  const { x_label, lines, accessibility_label } = props;

  const rows = useMemo(() => buildRows(props), [props]);

  const renderRow = (row: ScreenReaderRow) => {
    return (
      <tr key={row.key}>
        <td>{row.x_value}</td>
        {row.series_values.map((seriesValue) => (
          <td key={seriesValue.data_key}>{seriesValue.value}</td>
        ))}
      </tr>
    );
  };

  return (
    <div className="sr-only">
      <table>
        <caption>{accessibility_label}</caption>
        <thead>
          <tr>
            <th scope="col">{x_label}</th>
            {lines.map((line) => (
              <th key={line.data_key} scope="col">
                {line.label}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>{rows.map(renderRow)}</tbody>
      </table>
    </div>
  );
};

export default LineChartScreenReaderTable;
