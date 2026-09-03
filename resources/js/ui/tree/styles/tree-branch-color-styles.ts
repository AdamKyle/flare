import TreeColor from '../enums/tree-color';

/**
 * Branch stroke treatment for each `TreeColor`, using only the project's
 * named Flare palettes with light and dark coverage.
 */
const TREE_BRANCH_COLOR_STYLES: Record<TreeColor, string> = {
  [TreeColor.GLACIER]: 'stroke-glacier-500 dark:stroke-glacier-400',
  [TreeColor.DANUBE]: 'stroke-danube-500 dark:stroke-danube-400',
  [TreeColor.EMERALD]: 'stroke-emerald-500 dark:stroke-emerald-400',
  [TreeColor.ROSE]: 'stroke-rose-500 dark:stroke-rose-400',
  [TreeColor.MARIGOLD]: 'stroke-marigold-500 dark:stroke-marigold-400',
};

export default TREE_BRANCH_COLOR_STYLES;
