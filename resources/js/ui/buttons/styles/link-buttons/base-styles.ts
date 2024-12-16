export const baseStyles = (): string => {
  return (
    'inline-flex items-center justify-center px-0 py-0 text-sm font-medium ' +
    'transition-colors duration-200 ease-in-out focus:outline-none ' +
    'focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800 ' +
    'disabled:opacity-75 disabled:cursor-not-allowed underline rounded-sm'
  );
};
