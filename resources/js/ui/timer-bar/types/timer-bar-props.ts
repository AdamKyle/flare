import TimerBarSize from '../enums/timer-bar-size';

export default interface TimerBarProps {
  length: number;
  title: string;
  remaining?: number;
  size?: TimerBarSize;
  additional_css?: string;
}
