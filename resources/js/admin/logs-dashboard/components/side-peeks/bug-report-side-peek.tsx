import React from 'react';

import BugReportSidePeekProps from './types/bug-report-side-peek-props';

const DetailBlock = ({
  label,
  value,
  pre = false,
}: {
  label: string;
  value: React.ReactNode;
  pre?: boolean;
}) => {
  if (value === null || value === undefined || value === '') {
    return null;
  }

  return (
    <div>
      <dt className="text-xs font-semibold text-gray-500 uppercase dark:text-gray-400">
        {label}
      </dt>
      <dd
        className={
          pre
            ? 'mt-1 max-h-72 overflow-x-hidden overflow-y-auto rounded bg-gray-100 p-2 text-xs break-all whitespace-pre-wrap dark:bg-gray-800'
            : 'mt-1 text-sm break-words text-gray-900 dark:text-gray-100'
        }
      >
        {value}
      </dd>
    </div>
  );
};

export default function BugReportSidePeek({ bug }: BugReportSidePeekProps) {
  return (
    <div className="space-y-5 px-4 py-3">
      <p className="text-sm text-gray-500 dark:text-gray-400">
        {bug.occurrence_count} occurrence
        {bug.occurrence_count === 1 ? '' : 's'}
      </p>
      <dl className="space-y-4">
        <DetailBlock label="Fingerprint" value={bug.fingerprint} />
        <DetailBlock label="Status" value={bug.status} />
        <DetailBlock label="Severity" value={bug.severity} />
        <DetailBlock label="First Seen" value={bug.first_seen_at} />
        <DetailBlock label="Last Seen" value={bug.last_seen_at} />
        <DetailBlock label="Latest Message" value={bug.latest_message} />
        <DetailBlock
          label="Latest Stack Trace"
          value={bug.latest_stack_trace}
          pre
        />
        <DetailBlock
          label="Latest Raw Log Entry"
          value={bug.latest_raw_log_entry}
          pre
        />
      </dl>
      <section>
        <h4 className="text-sm font-semibold">Occurrence History</h4>
        <div className="mt-2 space-y-2">
          {bug.occurrences.map((occurrence, index) => (
            <div
              key={`${occurrence.occurred_at ?? 'unknown'}-${index}`}
              className="rounded border border-gray-200 p-3 text-sm dark:border-gray-700"
            >
              <div className="flex flex-wrap items-center gap-2">
                <span>{occurrence.occurred_at ?? 'Unknown time'}</span>
                {occurrence.level && (
                  <span className="text-xs uppercase">{occurrence.level}</span>
                )}
              </div>
              <p className="mt-1 text-gray-700 dark:text-gray-300">
                {occurrence.message}
              </p>
            </div>
          ))}
          {bug.occurrences.length === 0 && (
            <p className="text-sm text-gray-500">No occurrences loaded.</p>
          )}
        </div>
      </section>
    </div>
  );
}
