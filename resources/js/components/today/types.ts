export type AttentionSeverity = 'urgent' | 'warning' | 'info';

export interface TodaySessionItem {
  id: string;
  title: string;
  domain: string;
  domainLabel: string;
  href: string;
  moduleName?: string;
  currentStep?: string;
  lastActivityAt?: string;
  actionLabel?: string;
}

export interface RecommendationData {
  recommendationId: string;
  id: string; // The identity of the recommendation (action)
  title: string;
  domain: string;
  domainLabel: string;
  href: string;
  description: string;
  timeCommitment?: string;
  difficulty?: string;
  actionLabel?: string;
  rationaleText: string;
  targetCompetency?: string;
  unlockedCapabilities?: string[];
  prerequisiteChain?: string[];
  selectionRuleId?: string;
  selectedAt?: string;
  observedAt?: string;
  freshUntil?: string;
  target?: string;
}

export interface TodayAttentionItem {
  id: string;
  title: string;
  domain: string;
  domainLabel: string;
  href: string;
  severity: AttentionSeverity;
  reason: string;
  actionLabel?: string;
}

export interface TodayRecentContextItem {
  id: string;
  title: string;
  domain: string;
  domainLabel: string;
  href: string;
  timestamp: string;
  summary: string;
}

export interface TodayProgressProjection {
  milestoneTitle: string;
  verifiedCount: number;
  totalCount: number;
  statusSummary: string;
  targetHorizon?: string;
  evidenceRequirement?: string;
}

export type OrchestrationStatus = 'AVAILABLE' | 'EMPTY' | 'UNAVAILABLE' | 'ERROR' | 'STALE';

export interface OrchestrationNode<T> {
  status: OrchestrationStatus;
  data: T | null;
  message?: string;
  diagnosticId?: string;
  observedAt?: string;
  freshUntil?: string;
}

export interface TodayOrchestrationPayload {
  registeredDomainEntries: number;
  expectedDomainEntries: number;
  continueSession: OrchestrationNode<TodaySessionItem>;
  recommendation: OrchestrationNode<RecommendationData>;
  attentionItems: OrchestrationNode<TodayAttentionItem[]>;
  recentContext: OrchestrationNode<TodayRecentContextItem[]>;
  progressProjection: OrchestrationNode<TodayProgressProjection>;
}
