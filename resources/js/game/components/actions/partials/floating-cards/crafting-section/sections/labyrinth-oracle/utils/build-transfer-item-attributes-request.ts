import TransferItemAttributesRequestDefinition from '../api/definitions/transfer-item-attributes-request-definition';

export const buildTransferItemAttributesRequest = (
  sourceId: number | null,
  destinationId: number | null
): TransferItemAttributesRequestDefinition | null => {
  if (
    sourceId === null ||
    destinationId === null ||
    sourceId === destinationId
  ) {
    return null;
  }

  return {
    item_id_from: sourceId,
    item_id_to: destinationId,
  };
};
