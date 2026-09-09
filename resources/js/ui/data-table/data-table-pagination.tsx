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
      className="flex flex-col items-center justify-between gap-3 border-t border-gray-200 bg-gray-50 p-4 sm:flex-row dark:border-gray-700 dark:bg-gray-800"
    >
      <p
        role="status"
        aria-live="polite"
        className="text-sm text-gray-700 dark:text-gray-300"
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
          className="focus-visible:ring-brand-600 dark:focus-visible:ring-brand-400 rounded-md border border-gray-300 px-2 py-1 text-sm text-gray-700 focus:outline-none focus-visible:ring-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-200"
        >
          First
        </button>
        <button
          type="button"
          onClick={() => requestPageChange(current_page - 1)}
          disabled={isFirstPage}
          aria-label="Previous page"
          className="focus-visible:ring-brand-600 dark:focus-visible:ring-brand-400 rounded-md border border-gray-300 px-3 py-1 text-sm text-gray-700 focus:outline-none focus-visible:ring-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-200"
        >
          Previous
        </button>
        <button
          type="button"
          onClick={() => requestPageChange(current_page + 1)}
          disabled={isLastPage}
          aria-label="Next page"
          className="focus-visible:ring-brand-600 dark:focus-visible:ring-brand-400 rounded-md border border-gray-300 px-3 py-1 text-sm text-gray-700 focus:outline-none focus-visible:ring-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-200"
        >
          Next
        </button>
        <button
          type="button"
          onClick={() => requestPageChange(total_pages)}
          disabled={isLastPage}
          aria-label="Last page"
          className="focus-visible:ring-brand-600 dark:focus-visible:ring-brand-400 rounded-md border border-gray-300 px-2 py-1 text-sm text-gray-700 focus:outline-none focus-visible:ring-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-200"
        >
          Last
        </button>
      </div>
    </nav>
  );
};

export default DataTablePagination;
