import {
  DelveActiveStatusDefinition,
  DelveCompletedStatusDefinition,
  DelveStatusDefinition,
} from '../api/definitions/delve-status-definition';

export const isVisibleDelveStatus = (
  status: DelveStatusDefinition | null
): status is DelveActiveStatusDefinition | DelveCompletedStatusDefinition => {
  if (status === null) {
    return false;
  }

  return status.active || status.completed;
};
