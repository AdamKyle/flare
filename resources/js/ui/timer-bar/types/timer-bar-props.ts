import TimerBarSize from '../enums/timer-bar-size';

export default interface TimerBarProps {
  length: number;
  title: string;
  remaining?: number;
  complete_at?: string;
  detailed_time?: boolean;
  size?: TimerBarSize;
  additional_css?: string;
}
