import React, { useRef } from 'react';

import CharacterQueueTable from './character-queue-table';
import RequestHistory from './request-history';
import StaleQueueAlert from './stale-queue-alert';
import StaleQueueView from './stale-queue-view';
import StatusVolumeChart from './status-volume-chart';
import SummaryCards from './summary-cards';
import useRewardQueueDashboard from '../hooks/use-reward-queue-dashboard';

export default function RewardQueueDashboard() {
  const {
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
    update_filters: updateFilters,
    filter_by_status: filterByStatus,
    filters,
    set_request_page: setRequestPage,
    show_stale_view: showStaleView,
    set_show_stale_view: setShowStaleView,
    stale_queues: staleQueues,
    repairing,
    repair_queues: repairQueues,
  } = useRewardQueueDashboard();

  const requestHistoryRef = useRef<HTMLDivElement>(null);

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
            onFilter={(status) => filterByStatus(status)}
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
            onSelect={selectCharacter}
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
              onFiltersChange={updateFilters}
              onClearCharacter={clearSelectedCharacter}
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
