import type { EditorThemeClasses } from 'lexical';

export const mark_down_editor_theme: EditorThemeClasses = {
  paragraph: 'mb-2 leading-6 text-gray-900 dark:text-gray-100',
  quote:
    'border-l-4 pl-4 italic text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600',
  heading: {
    h1: 'mt-4 mb-2 text-3xl font-bold text-gray-900 dark:text-gray-100',
    h2: 'mt-3 mb-2 text-2xl font-semibold text-gray-900 dark:text-gray-100',
    h3: 'mt-2 mb-1.5 text-xl font-semibold text-gray-900 dark:text-gray-100',
  },
  list: {
    ul: 'list-disc pl-6 space-y-0.5 marker:text-gray-500 dark:marker:text-gray-400',
    ol: 'list-decimal pl-6 space-y-0.5 marker:text-gray-500 dark:marker:text-gray-400',
    listitem: 'my-0.5',
    nested: { listitem: 'list-inside' },
  },
  link: 'underline underline-offset-2 text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300',
  text: {
    bold: 'font-semibold',
    italic: 'italic',
  },
  ltr: 'text-left',
  rtl: 'text-right',
  error: 'text-red-600 dark:text-red-400',
} as const;
