import { useCallback, useEffect, useState } from 'react';

import useRewardQueueLiveRefresh from './use-reward-queue-live-refresh';
import useStaleRewardQueues from './use-stale-reward-queues';
import {
  CharacterRow,
  ChartPoint,
  ChartsResponse,
  RequestFiltersType,
  RewardRequest,
  Summary,
} from '../api/definitions/reward-queue-definition';
import { useRewardQueueApi } from '../api/hooks/use-reward-queue-api';
import rewardQueuePaginationAdapter from '../utils/reward-queue-pagination-adapter';

const EMPTY_SUMMARY: Summary = {
  queued: 0,
  pending: 0,
  processing: 0,
  resumable: 0,
  completed: 0,
  failed: 0,
};

const DEFAULT_FILTERS: RequestFiltersType = {
  status: '',
  priority: '',
  source_type: '',
  date_from: '',
  date_to: '',
  character_name: '',
  failed_reason: '',
  source_id: '',
};

/**
 * Owns every Battle Reward Queue monitoring data concern: fetch
 * orchestration, filters, pagination, and stale-queue repair. The
 * dashboard component only renders this hook's state; it never
 * coordinates requests itself.
 */
export default function useRewardQueueDashboard() {
  const {
    fetchRewardQueueSummary,
    fetchRewardQueueCharts,
    fetchRewardQueueCharacters,
    fetchRewardQueueStatusVolume,
    fetchCharacterRewardQueue,
    fetchRewardQueueRequests,
  } = useRewardQueueApi();

  const [summary, setSummary] = useState(EMPTY_SUMMARY);
  const [charts, setCharts] = useState<ChartsResponse>({
    last_hour: [],
    last_7_days: [],
    previous_7_days: [],
  });
  const [characters, setCharacters] = useState(
    rewardQueuePaginationAdapter<CharacterRow>({
      data: [],
      current_page: 1,
      last_page: 1,
      total: 0,
    })
  );
  const [requests, setRequests] = useState(
    rewardQueuePaginationAdapter<RewardRequest>({
      data: [],
      current_page: 1,
      last_page: 1,
      total: 0,
    })
  );
  const [selectedCharacter, setSelectedCharacter] =
    useState<CharacterRow | null>(null);
  const [detailCharts, setDetailCharts] = useState<
    Record<string, ChartPoint[]>
  >({});
  const [globalChart, setGlobalChart] = useState<ChartPoint[]>([]);
  const [range, setRange] = useState('7');
  const [characterPage, setCharacterPage] = useState(1);
  const [requestPage, setRequestPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');
  const [showStaleView, setShowStaleView] = useState(false);
  const [filters, setFilters] = useState<RequestFiltersType>(DEFAULT_FILTERS);

  const { staleQueues, repairing, refreshStaleQueues, repair } =
    useStaleRewardQueues();

  const refresh = useCallback(async () => {
    setError('');

    try {
      const [summaryData, chartsData, charactersData, globalData, requestData] =
        await Promise.all([
          fetchRewardQueueSummary(),
          fetchRewardQueueCharts(),
          fetchRewardQueueCharacters(characterPage),
          fetchRewardQueueStatusVolume(range),
          selectedCharacter
            ? fetchCharacterRewardQueue(
                selectedCharacter.character_id,
                filters,
                requestPage
              )
            : fetchRewardQueueRequests(filters, requestPage),
          refreshStaleQueues(),
        ]);

      setSummary(summaryData);
      setCharts(chartsData);
      setCharacters(charactersData);
      setGlobalChart(globalData);

      if ('requests' in requestData) {
        setRequests(requestData.requests);
        setDetailCharts(requestData.charts);
      } else {
        setRequests(requestData);
        setDetailCharts({});
      }
    } catch {
      setError('Reward queue data could not be loaded.');
    } finally {
      setLoading(false);
    }
  }, [
    characterPage,
    fetchCharacterRewardQueue,
    fetchRewardQueueCharacters,
    fetchRewardQueueCharts,
    fetchRewardQueueRequests,
    fetchRewardQueueStatusVolume,
    fetchRewardQueueSummary,
    filters,
    range,
    refreshStaleQueues,
    requestPage,
    selectedCharacter,
  ]);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  useRewardQueueLiveRefresh(refresh);

  const repairQueues = async (): Promise<void> => {
    setError('');
    setMessage('');

    try {
      const result = await repair();
      setMessage(
        `Recovered ${result.repaired_queue_state_count} queue states. Resumed ${result.resumed_processing_request_count} ledger-backed requests, marked ${result.legacy_failed_processing_request_count} legacy requests failed, and restarted ${result.restarted_processor_count} processors.`
      );
      await refresh();
    } catch {
      setError('Stale reward queues could not be repaired.');
    }
  };

  const selectCharacter = (character: CharacterRow): void => {
    setSelectedCharacter(character);
    setRequestPage(1);
  };

  const clearSelectedCharacter = (): void => {
    setSelectedCharacter(null);
    setRequestPage(1);
  };

  const filterByStatus = (status: string): void => {
    setFilters((previous) => ({ ...previous, status }));
    setRequestPage(1);
  };

  const updateFilters = (nextFilters: RequestFiltersType): void => {
    setFilters(nextFilters);
    setRequestPage(1);
  };

  return {
    loading,
    error,
    message,
    summary,
    charts,
    characters,
    requests,
    selected_character: selectedCharacter,
    select_character: selectCharacter,
    clear_selected_character: clearSelectedCharacter,
    detail_charts: detailCharts,
    global_chart: globalChart,
    range,
    set_range: setRange,
    set_character_page: setCharacterPage,
    request_page: requestPage,
    set_request_page: setRequestPage,
    filters,
    update_filters: updateFilters,
    filter_by_status: filterByStatus,
    show_stale_view: showStaleView,
    set_show_stale_view: setShowStaleView,
    stale_queues: staleQueues,
    repairing,
    repair_queues: repairQueues,
  };
}
