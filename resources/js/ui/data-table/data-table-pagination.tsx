import React, { ReactNode } from 'react';

import DataTablePaginationProps from 'ui/data-table/types/data-table-pagination-props';

const DataTablePagination = ({
  current_page,
  total_pages,
  total_records,
  on_page_change,
}: DataTablePaginationProps): ReactNode => {
  const isFirstPage = current_page <= 1;
  const isLastPage = current_page >= total_pages;

  const requestPageChange = (page: number): void => {
    const maximumPage = Math.max(total_pages, 1);
    const clampedPage = Math.min(Math.max(page, 1), maximumPage);

    on_page_change(clampedPage);
  };

  return (
    <nav
      aria-label="Table pagination"
      className="border-glacier-200 bg-glacier-50 dark:border-glacier-800 dark:bg-glacier-900 flex flex-col items-center justify-between gap-3 border-t p-4 sm:flex-row"
    >
      <p
        role="status"
        aria-live="polite"
        className="text-glacier-700 dark:text-glacier-300 text-sm"
      >
        {total_records} total record{total_records === 1 ? '' : 's'} &middot;
        Page {total_pages === 0 ? 0 : current_page} of {total_pages}
      </p>
      <div className="flex max-w-full flex-wrap items-center justify-center gap-2">
        <button
          type="button"
          onClick={() => requestPageChange(1)}
          disabled={isFirstPage}
          aria-label="First page"
          className="focus-visible:ring-glacier-400 border-glacier-300 text-glacier-700 dark:border-glacier-700 dark:text-glacier-200 rounded-md border px-2 py-1 text-sm focus:outline-none focus-visible:ring-2 disabled:cursor-not-allowed disabled:opacity-50"
        >
          First
        </button>
        <button
          type="button"
          onClick={() => requestPageChange(current_page - 1)}
          disabled={isFirstPage}
          aria-label="Previous page"
          className="focus-visible:ring-glacier-400 border-glacier-300 text-glacier-700 dark:border-glacier-700 dark:text-glacier-200 rounded-md border px-3 py-1 text-sm focus:outline-none focus-visible:ring-2 disabled:cursor-not-allowed disabled:opacity-50"
        >
          Previous
        </button>
        <button
          type="button"
          onClick={() => requestPageChange(current_page + 1)}
          disabled={isLastPage}
          aria-label="Next page"
          className="focus-visible:ring-glacier-400 border-glacier-300 text-glacier-700 dark:border-glacier-700 dark:text-glacier-200 rounded-md border px-3 py-1 text-sm focus:outline-none focus-visible:ring-2 disabled:cursor-not-allowed disabled:opacity-50"
        >
          Next
        </button>
        <button
          type="button"
          onClick={() => requestPageChange(total_pages)}
          disabled={isLastPage}
          aria-label="Last page"
          className="focus-visible:ring-glacier-400 border-glacier-300 text-glacier-700 dark:border-glacier-700 dark:text-glacier-200 rounded-md border px-2 py-1 text-sm focus:outline-none focus-visible:ring-2 disabled:cursor-not-allowed disabled:opacity-50"
        >
          Last
        </button>
      </div>
    </nav>
  );
};

export default DataTablePagination;
