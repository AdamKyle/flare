import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import ItemUsageCardProps from './types/item-usage-card-props';
import {
  ItemUsageBlockerDefinition,
  ItemUsageRelatedEntityDefinition,
} from '../api/definitions/item-usage-definition';
import { ItemApiMessages } from '../api/enums/item-api-messages';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const NAVIGABLE_RELATED_ENTITY_RESOURCES = new Set([
  'quest',
  'monster',
  'location',
]);

const ItemUsageCard = ({
  usage,
  loading,
  error,
  on_open_related_entity: onOpenRelatedEntity,
}: ItemUsageCardProps): ReactNode => {
  const renderRelatedEntity = (
    blockerKey: string,
    relatedEntity: ItemUsageRelatedEntityDefinition
  ): ReactNode => {
    const key = `${blockerKey}-${relatedEntity.id}`;

    if (
      !onOpenRelatedEntity ||
      !NAVIGABLE_RELATED_ENTITY_RESOURCES.has(relatedEntity.resource)
    ) {
      return <li key={key}>{relatedEntity.name}</li>;
    }

    return (
      <li key={key}>
        <button
          type="button"
          onClick={() =>
            onOpenRelatedEntity(relatedEntity.resource, relatedEntity.id)
          }
          className="text-danube-700 hover:text-danube-600 dark:text-danube-200 dark:hover:text-danube-100 decoration-danube-400 dark:decoration-danube-500 focus-visible:ring-danube-400 rounded-sm underline underline-offset-2 focus:outline-none focus-visible:ring-2"
        >
          {relatedEntity.name}
        </button>
      </li>
    );
  };

  const renderRelatedEntities = (
    blocker: ItemUsageBlockerDefinition
  ): ReactNode => {
    if (!blocker.related_entities || blocker.related_entities.length === 0) {
      return null;
    }

    return (
      <ul className="text-glacier-700 dark:text-glacier-300 list-disc pl-5 text-sm">
        {blocker.related_entities.map((relatedEntity) =>
          renderRelatedEntity(blocker.key, relatedEntity)
        )}
      </ul>
    );
  };

  const renderBlocker = (blocker: ItemUsageBlockerDefinition): ReactNode => (
    <li key={blocker.key} className="space-y-1 px-2 py-3">
      <p className="text-glacier-900 dark:text-glacier-100 font-medium">
        {blocker.label} ({blocker.count})
      </p>
      {renderRelatedEntities(blocker)}
    </li>
  );

  const renderBlockers = (): ReactNode => {
    if (!usage || usage.blockers.length === 0) {
      return (
        <p className="text-glacier-700 dark:text-glacier-300 text-sm">
          Nothing currently references this Item. It is safe to delete.
        </p>
      );
    }

    return (
      <div className="flex flex-col gap-3">
        <Alert variant={AlertVariant.WARNING}>
          This Item cannot be deleted until every relationship below is
          deliberately removed first.
        </Alert>
        <ul className="divide-glacier-200 dark:divide-glacier-800 divide-y">
          {usage.blockers.map(renderBlocker)}
        </ul>
      </div>
    );
  };

  const renderBody = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error) {
      return (
        <ApiErrorAlert apiError={error.message ?? ItemApiMessages.LoadUsage} />
      );
    }

    return renderBlockers();
  };

  return (
    <div>
      <h2 className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold">
        Usage / Deletion Impact
      </h2>
      {renderBody()}
    </div>
  );
};

export default ItemUsageCard;
