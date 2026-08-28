import clsx from 'clsx';
import React, { ReactNode } from 'react';

import DataTableActionDefinition from 'ui/data-table/types/data-table-action-definition';
import DataTableColumnDefinition from 'ui/data-table/types/data-table-column-definition';
import DataTableTableProps from 'ui/data-table/types/data-table-table-props';

const DataTableTable = <TRow,>({
  caption,
  rows,
  row_id,
  columns,
  empty_message,
  sort_key,
  sort_direction,
  on_sort_change,
  on_row_activate,
  row_actions,
}: DataTableTableProps<TRow>): ReactNode => {
  const renderSortIcon = (
    column: DataTableColumnDefinition<TRow>
  ): ReactNode => {
    const columnSortKey = column.sort_key ?? column.key;

    if (columnSortKey !== sort_key) {
      return <i className="fas fa-sort text-glacier-400" aria-hidden="true" />;
    }

    if (sort_direction === 'desc') {
      return <i className="fas fa-sort-down" aria-hidden="true" />;
    }

    return <i className="fas fa-sort-up" aria-hidden="true" />;
  };

  const resolveColumnSortDirection = (
    column: DataTableColumnDefinition<TRow>
  ): 'ascending' | 'descending' | 'none' => {
    const columnSortKey = column.sort_key ?? column.key;

    if (columnSortKey !== sort_key) {
      return 'none';
    }

    return sort_direction === 'desc' ? 'descending' : 'ascending';
  };

  const resolveSortActionLabel = (
    column: DataTableColumnDefinition<TRow>,
    columnSortDirection: 'ascending' | 'descending' | 'none'
  ): string => {
    if (columnSortDirection === 'ascending') {
      return `Sort by ${column.header}, currently ascending. Activate to sort descending.`;
    }

    if (columnSortDirection === 'descending') {
      return `Sort by ${column.header}, currently descending. Activate to sort ascending.`;
    }

    return `Sort by ${column.header}`;
  };

  const renderHeaderContent = (
    column: DataTableColumnDefinition<TRow>
  ): ReactNode => {
    if (!column.sortable || !on_sort_change) {
      return column.header;
    }

    const columnSortDirection = resolveColumnSortDirection(column);
    const sortActionLabel = resolveSortActionLabel(column, columnSortDirection);

    return (
      <button
        type="button"
        onClick={() => on_sort_change(column.sort_key ?? column.key)}
        aria-label={sortActionLabel}
        className="focus-visible:ring-glacier-500 dark:focus-visible:ring-glacier-300 inline-flex items-center gap-1 rounded-sm font-semibold focus:outline-none focus-visible:ring-2"
      >
        <span>{column.header}</span>
        {renderSortIcon(column)}
      </button>
    );
  };

  const renderHeaders = () =>
    columns.map((column, index) => (
      <th
        key={column.key}
        scope="col"
        aria-sort={
          column.sortable ? resolveColumnSortDirection(column) : undefined
        }
        className={clsx(
          'text-glacier-700 dark:text-glacier-200 px-3 py-2 text-left text-xs font-semibold tracking-wide uppercase',
          column.header_class_name
        )}
        style={
          column.minimum_width ? { minWidth: column.minimum_width } : undefined
        }
      >
        {index === 0 && on_row_activate && (
          <span className="sr-only">Activate</span>
        )}
        {renderHeaderContent(column)}
      </th>
    ));

  const renderRowActions = (row: TRow) => {
    if (!row_actions || row_actions.length === 0) {
      return null;
    }

    return row_actions.map((action: DataTableActionDefinition<TRow>) => (
      <button
        key={action.key}
        type="button"
        onClick={() => action.on_click(row)}
        aria-label={action.aria_label ? action.aria_label(row) : action.label}
        className="focus-visible:ring-glacier-500 dark:focus-visible:ring-glacier-300 text-danube-600 dark:text-danube-300 ml-2 rounded-sm text-sm font-medium hover:underline focus:outline-none focus-visible:ring-2"
      >
        {action.label}
      </button>
    ));
  };

  const renderIdentityCell = (
    row: TRow,
    column: DataTableColumnDefinition<TRow>
  ) => {
    if (!on_row_activate) {
      return column.value(row);
    }

    return (
      <>
        <button
          type="button"
          onClick={() => on_row_activate(row)}
          className="focus-visible:ring-glacier-500 dark:focus-visible:ring-glacier-300 text-danube-600 dark:text-danube-300 w-full rounded-sm text-left font-medium hover:underline focus:outline-none focus-visible:ring-2"
        >
          <span>{column.value(row)}</span>
        </button>
        {renderRowActions(row)}
      </>
    );
  };

  const renderCell = (
    row: TRow,
    column: DataTableColumnDefinition<TRow>,
    index: number
  ) => (
    <td
      key={column.key}
      className={clsx(
        'text-glacier-900 dark:text-glacier-100 px-3 py-2 align-top text-sm break-words',
        column.class_name
      )}
    >
      {index === 0 ? renderIdentityCell(row, column) : column.value(row)}
    </td>
  );

  const renderRow = (row: TRow) => (
    <tr
      key={row_id(row)}
      className="border-glacier-200 hover:bg-glacier-50 dark:border-glacier-800 dark:hover:bg-glacier-900 border-b last:border-0"
    >
      {columns.map((column, index) => renderCell(row, column, index))}
    </tr>
  );

  const renderEmptyRow = () => (
    <tr>
      <td
        colSpan={columns.length}
        className="text-glacier-600 dark:text-glacier-300 px-3 py-6 text-center text-sm"
      >
        {empty_message}
      </td>
    </tr>
  );

  const renderTableBody = (): ReactNode => {
    if (rows.length === 0) {
      return renderEmptyRow();
    }

    return rows.map(renderRow);
  };

  return (
    <div className="w-full overflow-x-auto">
      <table className="w-full border-collapse">
        <caption className="sr-only">{caption}</caption>
        <thead>
          <tr className="border-glacier-200 bg-glacier-100 dark:border-glacier-700 dark:bg-glacier-900 border-b">
            {renderHeaders()}
          </tr>
        </thead>
        <tbody>{renderTableBody()}</tbody>
      </table>
    </div>
  );
};

export default DataTableTable;
