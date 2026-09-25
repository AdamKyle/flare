import ExplorationChartPointDefinition from './exploration-chart-point-definition';
import ExplorationDamageDefinition from './exploration-damage-definition';
import ExplorationMonsterDefinition from './exploration-monster-definition';
import ExplorationTotalsDefinition from './exploration-totals-definition';
import { ExplorationPhase } from '../enums/exploration-phase';

export default interface ExplorationLogOutputDefinition {
  id: number;
  character_id: number;
  user_id: number;
  character_automation_id: number;
  monster_id: number;
  attack_type: string;
  started_at: string;
  ended_at: string | null;
  phase: ExplorationPhase | null;
  stopped_reason: string | null;
  stopped_by_player: boolean;
  fights: number;
  kills: number;
  weapon_damage: number;
  spell_damage: number;
  xp_gained: number;
  skill_xp_gained: number;
  faction_points_gained: number;
  currencies_gained: Record<string, number>;
  current_round_creatures: number;
  monster: ExplorationMonsterDefinition;
  totals: ExplorationTotalsDefinition;
  currencies: Record<string, number>;
  chart_points: ExplorationChartPointDefinition[];
  damage: ExplorationDamageDefinition;
  healing: number;
  blocked: number;
  duration: number;
  reason: string | null;
  message: string;
}
