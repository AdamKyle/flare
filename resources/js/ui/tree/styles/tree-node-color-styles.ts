import TreeColor from '../enums/tree-color';

interface TreeNodeColorStyle {
  border: string;
  focus_ring: string;
  interactive_fill_background: string;
  interactive_fill_foreground: string;
}

/**
 * Border, focus-ring, and interactive hover/focus fill treatment for each
 * `TreeColor`, using only the project's named Flare palettes with light and
 * dark coverage. `interactive_fill_background` is applied directly to an
 * interactive Tree node's own button. `interactive_fill_foreground` is a
 * `group-hover`/`group-focus-visible` text treatment meant to be applied by
 * the domain content rendered inside that button (see `QuestTreeNode`), so
 * text stays readable once the button's background fills with its status
 * color. The foreground is always a contrasting shade from the same named
 * palette as the background, never generic white/black.
 */
const TREE_NODE_COLOR_STYLES: Record<TreeColor, TreeNodeColorStyle> = {
  [TreeColor.GLACIER]: {
    border: 'border-glacier-500 dark:border-glacier-400',
    focus_ring:
      'focus-visible:ring-glacier-500 dark:focus-visible:ring-glacier-300',
    interactive_fill_background:
      'hover:bg-glacier-600 focus-visible:bg-glacier-600 dark:hover:bg-glacier-500 dark:focus-visible:bg-glacier-500',
    interactive_fill_foreground:
      'group-hover:text-glacier-100 group-focus-visible:text-glacier-100 dark:group-hover:text-glacier-950 dark:group-focus-visible:text-glacier-950',
  },
  [TreeColor.DANUBE]: {
    border: 'border-danube-600 dark:border-danube-400',
    focus_ring:
      'focus-visible:ring-danube-500 dark:focus-visible:ring-danube-300',
    interactive_fill_background:
      'hover:bg-danube-600 focus-visible:bg-danube-600 dark:hover:bg-danube-500 dark:focus-visible:bg-danube-500',
    interactive_fill_foreground:
      'group-hover:text-danube-100 group-focus-visible:text-danube-100 dark:group-hover:text-danube-950 dark:group-focus-visible:text-danube-950',
  },
  [TreeColor.EMERALD]: {
    border: 'border-emerald-600 dark:border-emerald-400',
    focus_ring:
      'focus-visible:ring-emerald-500 dark:focus-visible:ring-emerald-300',
    interactive_fill_background:
      'hover:bg-emerald-600 focus-visible:bg-emerald-600 dark:hover:bg-emerald-500 dark:focus-visible:bg-emerald-500',
    interactive_fill_foreground:
      'group-hover:text-emerald-100 group-focus-visible:text-emerald-100 dark:group-hover:text-emerald-950 dark:group-focus-visible:text-emerald-950',
  },
  [TreeColor.ROSE]: {
    border: 'border-rose-600 dark:border-rose-400',
    focus_ring: 'focus-visible:ring-rose-500 dark:focus-visible:ring-rose-300',
    interactive_fill_background:
      'hover:bg-rose-600 focus-visible:bg-rose-600 dark:hover:bg-rose-500 dark:focus-visible:bg-rose-500',
    interactive_fill_foreground:
      'group-hover:text-rose-100 group-focus-visible:text-rose-100 dark:group-hover:text-rose-950 dark:group-focus-visible:text-rose-950',
  },
  [TreeColor.MARIGOLD]: {
    border: 'border-marigold-600 dark:border-marigold-400',
    focus_ring:
      'focus-visible:ring-marigold-500 dark:focus-visible:ring-marigold-300',
    interactive_fill_background:
      'hover:bg-marigold-600 focus-visible:bg-marigold-600 dark:hover:bg-marigold-500 dark:focus-visible:bg-marigold-500',
    interactive_fill_foreground:
      'group-hover:text-marigold-100 group-focus-visible:text-marigold-100 dark:group-hover:text-marigold-950 dark:group-focus-visible:text-marigold-950',
  },
};

export default TREE_NODE_COLOR_STYLES;
