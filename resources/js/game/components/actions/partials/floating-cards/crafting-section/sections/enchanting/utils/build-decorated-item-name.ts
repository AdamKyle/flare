import CraftingItemPreviewDefinition from '../../../shared/api/definitions/crafting-item-preview-definition';

export const buildDecoratedItemName = (
  item: CraftingItemPreviewDefinition
): string => {
  const prefixName = item.item_prefix?.name;
  const suffixName = item.item_suffix?.name;

  let decoratedName = item.name;

  if (prefixName) {
    decoratedName = `*${prefixName}* ${item.name}`;
  }

  if (suffixName) {
    decoratedName = prefixName
      ? `${decoratedName} *${suffixName}*`
      : `${item.name} *${suffixName}*`;
  }

  return decoratedName;
};
