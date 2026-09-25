import { format, isValid, parseISO } from 'date-fns';

export const formatMessageTimestamp = (timestamp: string): string => {
  const parsed = parseISO(timestamp);

  if (!isValid(parsed)) {
    return '';
  }

  return format(parsed, 'PPp');
};
