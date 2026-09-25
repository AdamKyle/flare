import React, { ReactNode } from 'react';

const OnboardingSlidePlaceholder = (): ReactNode => {
  return (
    <div
      className="relative h-full w-full overflow-hidden bg-gray-200 dark:bg-gray-700"
      aria-hidden="true"
    >
      <div className="flex h-full w-full flex-col items-center justify-center gap-2 border-2 border-dashed border-gray-300 text-gray-500 dark:border-gray-600 dark:text-gray-400">
        <i className="far fa-image text-4xl md:text-6xl" />
        <span className="text-xs font-semibold tracking-wide uppercase md:text-sm">
          Image coming soon
        </span>
      </div>

      <div className="pointer-events-none absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-b from-transparent to-gray-100 md:hidden dark:to-gray-800" />
      <div className="pointer-events-none absolute inset-y-0 right-0 hidden w-1/2 bg-gradient-to-r from-transparent to-gray-100 md:block dark:to-gray-800" />
    </div>
  );
};

export default OnboardingSlidePlaceholder;
