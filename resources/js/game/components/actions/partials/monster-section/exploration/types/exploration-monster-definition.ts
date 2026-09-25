export default interface ExplorationMonsterDefinition {
  id: number;
  name: string | null;
  link: string;
  stats: Record<string, unknown>;
}
