export default interface ConjureCostSectionProps {
  gold_cost: number;
  gold_dust_cost: number;
  can_afford: boolean;
  on_request_private: () => void;
  on_request_public: () => void;
}
