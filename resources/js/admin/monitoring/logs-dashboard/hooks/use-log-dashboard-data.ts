import { useCallback, useEffect, useState } from 'react';

import UseLogDashboardDataDefinition from './definitions/use-log-dashboard-data-definition';
import UseLogDashboardDataParams from './definitions/use-log-dashboard-data-params';
import BugChartPointDefinition from '../api/definitions/bug-chart-point-definition';
import { LogsDashboardMessages } from '../api/enums/logs-dashboard-messages';
import {
  LogEntriesPage,
  LogSummary,
  SystemBugReport,
} from '../types/logs-dashboard';
import createEmptyLogEntriesPage from '../utils/create-empty-log-entries-page';

export default function useLogDashboardData({
  selected_file,
  filters,
  page,
  bug_range,
  fetch_log_entries,
  fetch_system_bugs,
  fetch_bug_chart,
}: UseLogDashboardDataParams): UseLogDashboardDataDefinition {
  const [entries, setEntries] = useState<LogEntriesPage>(
    createEmptyLogEntriesPage()
  );
  const [summary, setSummary] = useState<LogSummary>({
    total: 0,
    by_severity: {},
    chart: [],
  });
  const [bugs, setBugs] = useState<SystemBugReport[]>([]);
  const [bugChart, setBugChart] = useState<BugChartPointDefinition[]>([]);
  const [dataError, setDataError] = useState('');

  const loadData = useCallback(async () => {
    setDataError('');

    try {
      const [entriesData, bugData, bugChartData] = await Promise.all([
        fetch_log_entries(selected_file, filters, page),
        fetch_system_bugs(),
        fetch_bug_chart(bug_range),
      ]);

      setEntries(entriesData);
      setSummary(entriesData.summary);
      setBugs(bugData);
      setBugChart(bugChartData);
    } catch {
      setDataError(LogsDashboardMessages.LoadEntries);
    }
  }, [
    bug_range,
    fetch_bug_chart,
    fetch_log_entries,
    fetch_system_bugs,
    filters,
    page,
    selected_file,
  ]);

  useEffect(() => {
    if (!selected_file) {
      return;
    }

    void loadData();
  }, [selected_file, loadData]);

  return {
    entries,
    summary,
    bugs,
    bug_chart: bugChart,
    data_error: dataError,
    load_data: loadData,
  };
}
