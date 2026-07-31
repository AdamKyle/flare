import React from 'react';

import AdminPaginationControlsProps from '../types/admin-pagination-controls-props';

export default function AdminPaginationControls<T>({
  response,
  label,
  on_page_change,
}: AdminPaginationControlsProps<T>) {
  const currentPage = response?.meta.pagination.current_page ?? 1;
  const totalPages = response?.meta.pagination.total_pages ?? 1;

  return (
    <nav
      className="mt-4 flex flex-wrap items-center justify-between gap-2"
      aria-label={`${label} pagination`}
    >
      <span className="text-sm text-gray-600 dark:text-gray-300">
        Page {currentPage} of {totalPages}
      </span>
      <div className="flex gap-2">
        <button
          type="button"
          className="rounded border border-gray-300 px-3 py-2 text-sm disabled:opacity-50 dark:border-gray-600"
          disabled={currentPage <= 1}
          onClick={() => on_page_change(currentPage - 1)}
          aria-label={`Load previous ${label} page`}
        >
          Previous
        </button>
        <button
          type="button"
          className="rounded border border-gray-300 px-3 py-2 text-sm disabled:opacity-50 dark:border-gray-600"
          disabled={currentPage >= totalPages}
          onClick={() => on_page_change(currentPage + 1)}
          aria-label={`Load next ${label} page`}
        >
          Next
        </button>
      </div>
    </nav>
  );
}
