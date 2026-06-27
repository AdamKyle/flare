import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { useCallback } from 'react';

import {
  LogEntriesPage,
  LogFileInfo,
  LogFilters,
  LogSummary,
  LogsPollResponse,
  SystemBugReport,
} from '../types/logs-dashboard';

const base = '/admin/monitoring/logs';

export const useLogsApi = () => {
  const { apiHandler, getUrl } = useApiHandler();

  const fetchLogFiles = useCallback(async (): Promise<LogFileInfo[]> => {
    return apiHandler.get<LogFileInfo[], Record<string, never>>(
      getUrl(`${base}/files`)
    );
  }, [apiHandler, getUrl]);

  const fetchLogEntries = useCallback(
    async (
      fileKey: string,
      filters: LogFilters,
      page: number
    ): Promise<LogEntriesPage> => {
      return apiHandler.get<
        LogEntriesPage,
        LogFilters & { file: string; page: number }
      >(getUrl(`${base}/entries`), {
        params: { file: fileKey, ...filters, page },
      });
    },
    [apiHandler, getUrl]
  );

  const fetchLogSummary = useCallback(
    async (fileKey: string, filters: LogFilters): Promise<LogSummary> => {
      return apiHandler.get<LogSummary, LogFilters & { file: string }>(
        getUrl(`${base}/summary`),
        {
          params: { file: fileKey, ...filters },
        }
      );
    },
    [apiHandler, getUrl]
  );

  const pollLogs = useCallback(
    async (fileKey: string, filters: LogFilters): Promise<LogsPollResponse> => {
      return apiHandler.get<LogsPollResponse, LogFilters & { file: string }>(
        getUrl(`${base}/poll`),
        {
          params: { file: fileKey, ...filters },
        }
      );
    },
    [apiHandler, getUrl]
  );

  const fetchSystemBugs = useCallback(async (): Promise<SystemBugReport[]> => {
    return apiHandler.get<SystemBugReport[], Record<string, never>>(
      getUrl(`${base}/bugs`)
    );
  }, [apiHandler, getUrl]);

  const fetchBugChart = useCallback(
    async (
      days: number
    ): Promise<Array<{ period: string; occurrences: number }>> => {
      return apiHandler.get<
        Array<{ period: string; occurrences: number }>,
        { days: number }
      >(getUrl(`${base}/bug-chart`), { params: { days } });
    },
    [apiHandler, getUrl]
  );

  return {
    fetchLogFiles,
    fetchLogEntries,
    fetchLogSummary,
    pollLogs,
    fetchSystemBugs,
    fetchBugChart,
  };
};
