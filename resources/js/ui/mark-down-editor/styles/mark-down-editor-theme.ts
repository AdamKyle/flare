import type { EditorThemeClasses } from 'lexical';

export const mark_down_editor_theme: EditorThemeClasses = {
  paragraph: 'mb-3 leading-7 text-gray-900 dark:text-gray-100',
  quote:
    'border-l-4 pl-4 italic text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600',
  heading: {
    h1: 'mt-6 mb-3 text-3xl font-bold text-gray-900 dark:text-gray-100',
    h2: 'mt-5 mb-3 text-2xl font-semibold text-gray-900 dark:text-gray-100',
    h3: 'mt-4 mb-2 text-xl font-semibold text-gray-900 dark:text-gray-100',
  },
  list: {
    ul: 'list-disc pl-6 space-y-1 marker:text-gray-500 dark:marker:text-gray-400',
    ol: 'list-decimal pl-6 space-y-1 marker:text-gray-500 dark:marker:text-gray-400',
    listitem: 'my-1',
    nested: { listitem: 'list-inside' },
  },
  link: 'underline underline-offset-2 text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300',
  code: 'block rounded-md bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 font-mono text-sm p-3 overflow-x-auto',
  text: {
    bold: 'font-semibold',
    italic: 'italic',
    underline: 'underline underline-offset-2',
    strikethrough: 'line-through',
    code: 'rounded bg-gray-100 dark:bg-gray-800 px-1 py-0.5 font-mono text-[0.9em]',
  },
  ltr: 'text-left',
  rtl: 'text-right',
  table: 'w-full border-collapse my-3',
  tableCell: 'border border-gray-300 dark:border-gray-700 p-2 align-top',
  tableRow: '',
  tableAddColumns: '',
  tableAddRows: '',
  error: 'text-red-600 dark:text-red-400',
} as const;
