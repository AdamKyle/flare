import React from 'react';

import LogEntrySidePeekProps from './types/log-entry-side-peek-props';

const formatJson = (value: unknown): string => {
  if (value === null || value === undefined || value === '') {
    return '-';
  }

  if (typeof value === 'string') {
    try {
      return JSON.stringify(JSON.parse(value), null, 2);
    } catch {
      return value;
    }
  }

  return JSON.stringify(value, null, 2);
};

export default function LogEntrySidePeek({ entry }: LogEntrySidePeekProps) {
  const codeBlock =
    'rounded-md border border-gray-200 bg-gray-50 p-3 text-xs font-mono text-gray-800 whitespace-pre-wrap break-words overflow-x-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200';
  const label =
    'text-xs font-semibold uppercase text-gray-500 dark:text-gray-400';
  const value = 'text-sm break-words text-gray-900 dark:text-gray-100';

  return (
    <div className="space-y-6 px-4 py-3">
      <div className="grid gap-4 sm:grid-cols-2">
        <div className="space-y-1">
          <p className={label}>Timestamp</p>
          <p className={value}>{entry.timestamp ?? '-'}</p>
        </div>
        <div className="space-y-1">
          <p className={label}>Level</p>
          <p className={value}>{entry.severity}</p>
        </div>
        <div className="space-y-1">
          <p className={label}>Channel / File</p>
          <p className={value}>{entry.channel ?? entry.file_path ?? '-'}</p>
        </div>
        {entry.exception_class && (
          <div className="space-y-1">
            <p className={label}>Exception Class</p>
            <p className={value}>{entry.exception_class}</p>
          </div>
        )}
      </div>

      <div className="space-y-2">
        <p className={label}>Message</p>
        <p className={value}>{entry.message}</p>
      </div>

      {(entry.exception_file || entry.exception_line) && (
        <div className="space-y-2">
          <p className={label}>File / Line</p>
          <p className={value}>
            {entry.exception_file ?? ''}
            {entry.exception_line ? `:${entry.exception_line}` : ''}
          </p>
        </div>
      )}

      {entry.context && (
        <div className="space-y-2">
          <p className={label}>Context</p>
          <pre className={codeBlock}>{formatJson(entry.context)}</pre>
        </div>
      )}

      {entry.stack_trace && (
        <div className="space-y-2">
          <p className={label}>Stack Trace</p>
          <pre className={codeBlock}>{entry.stack_trace}</pre>
        </div>
      )}

      {entry.raw_log_entry && (
        <div className="space-y-2">
          <p className={label}>Raw Log Entry</p>
          <pre className={codeBlock}>{formatJson(entry.raw_log_entry)}</pre>
        </div>
      )}
    </div>
  );
}
