import { intervalToDuration } from 'date-fns';
import { match, P } from 'ts-pattern';

const pluralizeUnit = (value: number, unit: string): string =>
  `${value} ${unit}${value === 1 ? '' : 's'}`;

export const formatRemainingTime = (remainingSeconds: number): string => {
  const duration = intervalToDuration({
    start: 0,
    end: remainingSeconds * 1000,
  });

  return match(duration)
    .with(
      P.when((d) => (d.days ?? 0) > 0),
      (d) => pluralizeUnit(d.days ?? 0, 'day')
    )
    .with(
      P.when((d) => (d.hours ?? 0) > 0),
      (d) => pluralizeUnit(d.hours ?? 0, 'hour')
    )
    .with(
      P.when((d) => (d.minutes ?? 0) > 0),
      (d) => pluralizeUnit(d.minutes ?? 0, 'minute')
    )
    .otherwise((d) => pluralizeUnit(d.seconds ?? 0, 'second'));
};

export const formatDetailedRemainingTime = (
  remainingSeconds: number
): string => {
  const clamped = Math.max(0, remainingSeconds);

  const duration = intervalToDuration({
    start: 0,
    end: clamped * 1000,
  });

  const days = duration.days ?? 0;
  const hours = duration.hours ?? 0;
  const minutes = duration.minutes ?? 0;
  const seconds = duration.seconds ?? 0;

  const parts: string[] = [];

  if (days > 0) {
    parts.push(pluralizeUnit(days, 'day'));
  }

  if (hours > 0) {
    parts.push(pluralizeUnit(hours, 'hour'));
  }

  if (minutes > 0) {
    parts.push(pluralizeUnit(minutes, 'minute'));
  }

  parts.push(pluralizeUnit(seconds, 'second'));

  return parts.join(' ');
};
