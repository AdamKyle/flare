import React from 'react';

import MonitoringCard from './monitoring-card';
import { ActiveExplorer } from '../types/exploration-monitoring';

export default function ActiveExplorersTable({
  explorers,
}: {
  explorers: ActiveExplorer[];
}) {
  return (
    <MonitoringCard
      title="Currently Exploring"
      description="Characters with an active exploration automation."
    >
      {explorers.length === 0 ? (
        <p className="text-sm text-gray-600 dark:text-gray-300">
          No characters are currently exploring.
        </p>
      ) : (
        <div className="overflow-x-auto">
          <table className="w-full min-w-[600px] text-left text-sm">
            <thead>
              <tr className="border-b dark:border-gray-700">
                <th scope="col" className="p-2">
                  Character
                </th>
                <th scope="col" className="p-2">
                  Monster
                </th>
                <th scope="col" className="p-2">
                  Attack type
                </th>
                <th scope="col" className="p-2">
                  Started at
                </th>
                <th scope="col" className="p-2">
                  Ends at
                </th>
              </tr>
            </thead>
            <tbody>
              {explorers.map((explorer) => (
                <tr
                  className="border-t dark:border-gray-700"
                  key={explorer.character_id}
                >
                  <td className="p-2 font-medium">
                    {explorer.character_name ?? '—'}
                  </td>
                  <td className="p-2">{explorer.monster_name ?? '—'}</td>
                  <td className="p-2">{explorer.attack_type}</td>
                  <td className="p-2">{explorer.started_at ?? '—'}</td>
                  <td className="p-2">{explorer.completed_at ?? '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </MonitoringCard>
  );
}
