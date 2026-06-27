import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';
import React, { useCallback, useEffect, useRef, useState } from 'react';

import CharacterQueueTable from './character-queue-table';
import RequestHistory from './request-history';
import StaleQueueAlert from './stale-queue-alert';
import StaleQueueView from './stale-queue-view';
import StatusVolumeChart from './status-volume-chart';
import SummaryCards from './summary-cards';
import { useRewardQueueApi } from '../ajax/reward-queue-api';
import useRewardQueueLiveRefresh from '../hooks/use-reward-queue-live-refresh';
import useStaleRewardQueues from '../hooks/use-stale-reward-queues';
import {
  CharacterRow,
  ChartPoint,
  ChartsResponse,
  RequestFiltersType,
  RewardRequest,
  Summary,
} from '../types/reward-queue';

const emptySummary: Summary = {
  queued: 0,
  pending: 0,
  processing: 0,
  resumable: 0,
  completed: 0,
  failed: 0,
};

const emptyPaginatedResponse = <T,>(): PaginatedApiResponseDefinition<T[]> =>
  ({
    data: [],
    meta: {
      can_load_more: false,
      pagination: {
        count: 0,
        [`current${'_'}page`]: 1,
        links: { next: '', prev: '' },
        per_page: 10,
        total: 0,
        total_pages: 1,
      },
    },
  }) as PaginatedApiResponseDefinition<T[]>;

export default function RewardQueueDashboard() {
  const {
    fetchRewardQueueSummary,
    fetchRewardQueueCharts,
    fetchRewardQueueCharacters,
    fetchRewardQueueStatusVolume,
    fetchCharacterRewardQueue,
    fetchRewardQueueRequests,
  } = useRewardQueueApi();

  const [summary, setSummary] = useState(emptySummary);
  const [charts, setCharts] = useState<ChartsResponse>({
    last_hour: [],
    last_7_days: [],
    previous_7_days: [],
  });
  const [characters, setCharacters] = useState<
    PaginatedApiResponseDefinition<CharacterRow[]>
  >(emptyPaginatedResponse());
  const [requests, setRequests] = useState<
    PaginatedApiResponseDefinition<RewardRequest[]>
  >(emptyPaginatedResponse());
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
  const [filters, setFilters] = useState<RequestFiltersType>({
    status: '',
    priority: '',
    source_type: '',
    date_from: '',
    date_to: '',
    character_name: '',
    failed_reason: '',
    source_id: '',
  });
  const requestHistoryRef = useRef<HTMLDivElement>(null);
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

  const repairQueues = async () => {
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

  return (
    <div className="space-y-5 pb-16 text-gray-900 dark:text-gray-100">
      {loading && (
        <p role="status" aria-live="polite">
          Loading reward queue data…
        </p>
      )}
      {error && (
        <p
          className="rounded border border-red-400 bg-red-50 p-3 text-red-800 dark:bg-red-950 dark:text-red-100"
          role="alert"
        >
          {error}
        </p>
      )}
      {message && (
        <p
          className="rounded border border-green-400 bg-green-50 p-3 text-green-800 dark:bg-green-950 dark:text-green-100"
          role="status"
          aria-live="polite"
        >
          {message}
        </p>
      )}

      {showStaleView ? (
        <StaleQueueView
          staleQueues={staleQueues}
          repairing={repairing}
          onBack={() => setShowStaleView(false)}
          onRepair={() => void repairQueues()}
        />
      ) : (
        <>
          {staleQueues.length > 0 && (
            <StaleQueueAlert
              count={staleQueues.length}
              repairing={repairing}
              onView={() => setShowStaleView(true)}
              onRepair={() => void repairQueues()}
            />
          )}
          <SummaryCards
            summary={summary}
            onFilter={(status) => {
              setFilters((prev) => ({ ...prev, status }));
              setRequestPage(1);
            }}
          />
          <div className="grid gap-4 xl:grid-cols-3">
            <StatusVolumeChart
              title="Last hour"
              description="Request volume by status during the last 60 minutes."
              points={charts.last_hour}
            />
            <StatusVolumeChart
              title="Last 7 days"
              description="Current seven-day reward request volume."
              points={charts.last_7_days}
            />
            <StatusVolumeChart
              title="Previous 7 days"
              description="The previous completed seven-day period."
              points={charts.previous_7_days}
            />
          </div>
          <CharacterQueueTable
            characters={characters}
            onSelect={(character) => {
              setSelectedCharacter(character);
              setRequestPage(1);
            }}
            onPageChange={setCharacterPage}
          />
          {selectedCharacter &&
            ['1', '7', '14', '30'].map((days) => (
              <StatusVolumeChart
                key={days}
                title={`${selectedCharacter.character_name}: ${days} day${days === '1' ? '' : 's'}`}
                description="Character-specific request volume by status."
                points={detailCharts[days] ?? []}
              />
            ))}
          <div ref={requestHistoryRef}>
            <RequestHistory
              selectedCharacter={selectedCharacter}
              requests={requests}
              filters={filters}
              onFiltersChange={(nextFilters) => {
                setFilters(nextFilters);
                setRequestPage(1);
              }}
              onClearCharacter={() => {
                setSelectedCharacter(null);
                setRequestPage(1);
              }}
              onPageChange={setRequestPage}
            />
          </div>
          <div>
            <label className="mb-2 block text-sm font-medium">
              Global status range
              <select
                className="ml-2 rounded border border-gray-300 bg-white p-2 dark:border-gray-600 dark:bg-gray-800"
                value={range}
                onChange={(event) => setRange(event.target.value)}
              >
                {[1, 7, 14, 30, 60, 120, 365].map((days) => (
                  <option value={days} key={days}>
                    {days} day{days === 1 ? '' : 's'}
                  </option>
                ))}
              </select>
            </label>
            <StatusVolumeChart
              title="Global status volume"
              description={`Completed, failed, pending, and processing requests during the selected ${range}-day range.`}
              points={globalChart}
            />
          </div>
        </>
      )}
    </div>
  );
}
