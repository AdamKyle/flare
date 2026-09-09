import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import DataTableFilters from 'ui/data-table/data-table-filters';
import DataTablePagination from 'ui/data-table/data-table-pagination';
import DataTableTable from 'ui/data-table/data-table-table';
import DataTableProps from 'ui/data-table/types/data-table-props';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const DataTable = <TRow,>(props: DataTableProps<TRow>): ReactNode => {
  const renderBody = () => {
    if (props.loading) {
      return (
        <div className="p-6" role="status" aria-live="polite">
          <InfiniteLoader />
        </div>
      );
    }

    if (props.error) {
      return (
        <div className="p-4">
          <ApiErrorAlert apiError={props.error} />
        </div>
      );
    }

    return (
      <>
        <DataTableTable
          caption={props.caption}
          rows={props.rows}
          row_id={props.row_id}
          columns={props.columns}
          empty_message={props.empty_message}
          sort_key={props.sort_key}
          sort_direction={props.sort_direction}
          on_sort_change={props.on_sort_change}
          on_row_activate={props.on_row_activate}
          row_actions={props.row_actions}
        />
        <DataTablePagination
          current_page={props.current_page}
          total_pages={props.total_pages}
          total_records={props.total_records}
          on_page_change={props.on_page_change}
        />
      </>
    );
  };

  return (
    <div className="w-full min-w-0 overflow-hidden rounded-md border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
      <DataTableFilters
        id_prefix={props.id_prefix}
        search_label={props.search_label}
        search_value={props.search_value}
        on_search_change={props.on_search_change}
        filters={props.filters}
      />
      {renderBody()}
    </div>
  );
};

export default DataTable;
