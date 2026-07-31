import { ChartPoint } from '../api/definitions/reward-queue-definition';

export interface StatusVolumeChartProps {
  title: string;
  description: string;
  points: ChartPoint[];
}

export interface ChartPointsProps {
  points: ChartPoint[];
}
