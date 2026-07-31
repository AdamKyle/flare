import { useEffect } from 'react';

import UseLogPollingParams from './definitions/use-log-polling-params';
import { LOG_POLLING_INTERVAL_MS } from '../values/log-polling';

export default function useLogPolling({
  selected_file,
  filters,
  poll_logs,
  on_poll,
  on_error,
}: UseLogPollingParams) {
  useEffect(() => {
    if (!selected_file) {
      return;
    }

    const interval = window.setInterval(() => {
      poll_logs(selected_file, filters).then(on_poll).catch(on_error);
    }, LOG_POLLING_INTERVAL_MS);

    return () => window.clearInterval(interval);
  }, [filters, on_error, on_poll, poll_logs, selected_file]);
}
