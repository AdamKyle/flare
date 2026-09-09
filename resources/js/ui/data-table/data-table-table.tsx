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
      return <i className="fas fa-sort text-gray-400" aria-hidden="true" />;
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
        className="focus-visible:ring-brand-600 dark:focus-visible:ring-brand-400 inline-flex items-center gap-1 rounded-sm font-semibold focus:outline-none focus-visible:ring-2"
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
          'px-3 py-2.5 text-left text-xs font-semibold tracking-wide text-gray-700 uppercase dark:text-gray-300',
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
        className="focus-visible:ring-brand-600 dark:focus-visible:ring-brand-400 ml-2 rounded-sm text-sm font-medium text-gray-700 hover:text-gray-900 hover:underline focus:outline-none focus-visible:ring-2 dark:text-gray-300 dark:hover:text-gray-100"
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
          className="focus-visible:ring-brand-600 dark:focus-visible:ring-brand-400 w-full rounded-sm text-left font-semibold text-gray-900 hover:underline focus:outline-none focus-visible:ring-2 dark:text-gray-100"
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
        'px-3 py-2.5 align-top text-sm break-words text-gray-900 dark:text-gray-100',
        column.class_name
      )}
    >
      {index === 0 ? renderIdentityCell(row, column) : column.value(row)}
    </td>
  );

  const renderRow = (row: TRow) => (
    <tr
      key={row_id(row)}
      className="border-b border-gray-200 last:border-0 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700"
    >
      {columns.map((column, index) => renderCell(row, column, index))}
    </tr>
  );

  const renderEmptyRow = () => (
    <tr>
      <td
        colSpan={columns.length}
        className="px-3 py-6 text-center text-sm text-gray-600 dark:text-gray-300"
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
          <tr className="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
            {renderHeaders()}
          </tr>
        </thead>
        <tbody>{renderTableBody()}</tbody>
      </table>
    </div>
  );
};

export default DataTableTable;
