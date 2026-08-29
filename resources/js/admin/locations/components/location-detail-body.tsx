import React, { ReactNode } from 'react';
import ReactMarkdown from 'react-markdown';

import LocationDetailBodyProps from './types/location-detail-body-props';
import { LocationApiMessages } from '../api/enums/location-api-messages';
import { LOCATION_TYPE_LABELS } from '../enums/location-type';

import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import DataTablePagination from 'ui/data-table/data-table-pagination';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const relatedItemButtonClasses =
  'text-danube-700 hover:text-danube-600 dark:text-danube-300 dark:hover:text-danube-200 focus-visible:ring-danube-400 rounded font-medium underline-offset-2 hover:underline focus:outline-none focus-visible:ring-2';

/**
 * Render the canonical read-only Location detail body: Identity,
 * Description, Rules (with clickable required/reward Item identities), and
 * a paginated Quest Items Dropped Here relationship. Reused by both the
 * standalone Location show screen and the Game Map Location side-peek so
 * every entry point renders the same factual content.
 */
const LocationDetailBody = ({
  location,
  quest_items: questItems,
  on_open_related_item: onOpenRelatedItem,
  on_open_quest_item: onOpenQuestItem,
}: LocationDetailBodyProps): ReactNode => {
  const renderDropModeNotice = (): ReactNode => {
    if (location.is_cave_of_memories) {
      return (
        <Alert variant={AlertVariant.INFO}>
          Quest Items at the Cave of Memories are tied to Delve survival and
          progression, not ordinary manual fighting.
          {location.hours_to_drop !== null &&
            ` Drops become available after ${location.hours_to_drop} hour(s).`}
          {location.minutes_between_delve_fights !== null &&
            ` Delve fights are available every ${location.minutes_between_delve_fights} minute(s).`}
        </Alert>
      );
    }

    if (location.manual_fighting_only) {
      return (
        <Alert variant={AlertVariant.INFO}>
          These quest items can only drop while manually fighting at this
          location. Auto battle and exploration do not award these
          manual-location quest drops.
        </Alert>
      );
    }

    return null;
  };

  const renderRequiredQuestItem = (): ReactNode => {
    if (!location.required_quest_item) {
      return 'None';
    }

    const requiredQuestItem = location.required_quest_item;

    return (
      <button
        type="button"
        onClick={() => onOpenRelatedItem(requiredQuestItem)}
        className={relatedItemButtonClasses}
      >
        {requiredQuestItem.name}
      </button>
    );
  };

  const renderQuestRewardItem = (): ReactNode => {
    if (!location.quest_reward_item) {
      return 'None';
    }

    const questRewardItem = location.quest_reward_item;

    return (
      <button
        type="button"
        onClick={() => onOpenRelatedItem(questRewardItem)}
        className={relatedItemButtonClasses}
      >
        {questRewardItem.name}
      </button>
    );
  };

  const renderQuestItems = (): ReactNode => {
    if (questItems.loading) {
      return <InfiniteLoader />;
    }

    if (questItems.error) {
      return (
        <ApiErrorAlert
          apiError={
            questItems.error.message ?? LocationApiMessages.LoadQuestItems
          }
        />
      );
    }

    if (questItems.data.length === 0) {
      return (
        <p className="text-glacier-700 dark:text-glacier-300 text-sm">
          No quest Items drop at this Location.
        </p>
      );
    }

    return (
      <>
        <ul className="divide-glacier-200 dark:divide-glacier-800 divide-y">
          {questItems.data.map((item) => (
            <li key={item.item_id}>
              <button
                type="button"
                onClick={() => onOpenQuestItem(item)}
                className="hover:bg-glacier-50 dark:hover:bg-glacier-900 focus-visible:ring-glacier-400 flex w-full items-center justify-between gap-3 px-2 py-3 text-left focus:outline-none focus-visible:ring-2"
              >
                <span className="text-glacier-900 dark:text-glacier-100 font-medium">
                  {item.name}
                </span>
                <span className="text-glacier-600 dark:text-glacier-400 text-sm">
                  {item.effect ?? 'View details'}
                </span>
              </button>
            </li>
          ))}
        </ul>
        <DataTablePagination
          current_page={questItems.page}
          total_pages={questItems.total_pages}
          total_records={questItems.total_records}
          on_page_change={questItems.set_page}
        />
      </>
    );
  };

  return (
    <div className="flex flex-col gap-6">
      <section>
        <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
          Identity
        </h2>
        <Dl>
          <Dt>Map</Dt>
          <Dd>{location.game_map.name}</Dd>
          <Dt>Type</Dt>
          <Dd>
            {location.type === null
              ? 'None'
              : LOCATION_TYPE_LABELS[location.type]}
          </Dd>
          <Dt>Coordinates</Dt>
          <Dd>
            X {location.x}, Y {location.y}
          </Dd>
        </Dl>
      </section>

      <section>
        <h2 className="text-glacier-900 dark:text-glacier-100 mb-1 text-sm font-semibold">
          Description
        </h2>
        {location.description ? (
          <div className="text-glacier-700 dark:text-glacier-300 min-w-0 text-sm break-words">
            <ReactMarkdown>{location.description}</ReactMarkdown>
          </div>
        ) : (
          <p className="text-glacier-700 dark:text-glacier-300 text-sm">
            No description.
          </p>
        )}
      </section>

      <section>
        <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
          Rules
        </h2>
        <Dl>
          <Dt>Is Port</Dt>
          <Dd>{location.is_port ? 'Yes' : 'No'}</Dd>
          <Dt>Players May Enter</Dt>
          <Dd>{location.can_players_enter ? 'Yes' : 'No'}</Dd>
          <Dt>Auto Battle Allowed</Dt>
          <Dd>{location.can_auto_battle ? 'Yes' : 'No'}</Dd>
          <Dt>Required Quest Item</Dt>
          <Dd>{renderRequiredQuestItem()}</Dd>
          <Dt>Quest Reward Item</Dt>
          <Dd>{renderQuestRewardItem()}</Dd>
          <Dt>Hours to Drop</Dt>
          <Dd>{location.hours_to_drop ?? 'None'}</Dd>
          <Dt>Minutes Between Delve Fights</Dt>
          <Dd>{location.minutes_between_delve_fights ?? 'None'}</Dd>
        </Dl>
      </section>

      <section>
        <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
          Quest Items Dropped Here ({location.quest_item_drop_count})
        </h2>
        {renderDropModeNotice()}
        <div className="mt-3">{renderQuestItems()}</div>
      </section>
    </div>
  );
};

export default LocationDetailBody;
