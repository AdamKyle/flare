import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { useCallback } from 'react';

import UseLogsApiDefinition from './definitions/use-logs-api-definition';
import {
  LogEntriesPage,
  LogFileInfo,
  LogFilters,
  LogsPollResponse,
  SystemBugReport,
} from '../../types/logs-dashboard';
import BugChartPointDefinition from '../definitions/bug-chart-point-definition';
import LogEntryDetailDefinition from '../definitions/log-entry-detail-definition';
import LogEntryDetailRequestDefinition from '../definitions/log-entry-detail-request-definition';
import { LogsApiUrls } from '../enums/logs-api-urls';

export const useLogsApi = (): UseLogsApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const fetchLogFiles = useCallback(async (): Promise<LogFileInfo[]> => {
    return apiHandler.get<LogFileInfo[], Record<string, never>>(
      getUrl(LogsApiUrls.FILES)
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
      >(getUrl(LogsApiUrls.ENTRIES), {
        params: { file: fileKey, ...filters, page },
      });
    },
    [apiHandler, getUrl]
  );

  const fetchLogEntryDetail = useCallback(
    async (
      fileKey: string,
      detailId: string
    ): Promise<LogEntryDetailDefinition> =>
      apiHandler.get<LogEntryDetailDefinition, LogEntryDetailRequestDefinition>(
        getUrl(LogsApiUrls.ENTRY_DETAIL),
        { params: { file: fileKey, detail_id: detailId } }
      ),
    [apiHandler, getUrl]
  );

  const pollLogs = useCallback(
    async (fileKey: string, filters: LogFilters): Promise<LogsPollResponse> => {
      return apiHandler.get<LogsPollResponse, LogFilters & { file: string }>(
        getUrl(LogsApiUrls.POLL),
        {
          params: { file: fileKey, ...filters },
        }
      );
    },
    [apiHandler, getUrl]
  );

  const fetchSystemBugs = useCallback(async (): Promise<SystemBugReport[]> => {
    return apiHandler.get<SystemBugReport[], Record<string, never>>(
      getUrl(LogsApiUrls.BUGS)
    );
  }, [apiHandler, getUrl]);

  const fetchBugChart = useCallback(
    async (days: number): Promise<BugChartPointDefinition[]> => {
      return apiHandler.get<BugChartPointDefinition[], { days: number }>(
        getUrl(LogsApiUrls.BUG_CHART),
        { params: { days } }
      );
    },
    [apiHandler, getUrl]
  );

  return {
    fetchLogFiles,
    fetchLogEntries,
    fetchLogEntryDetail,
    pollLogs,
    fetchSystemBugs,
    fetchBugChart,
  };
};
