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

export interface TodayNextActionItem {
  id: string;
  title: string;
  domain: string;
  domainLabel: string;
  href: string;
  description: string;
  timeCommitment?: string;
  difficulty?: string;
  actionLabel?: string;
}

export interface TodayRationaleItem {
  id: string;
  text: string;
  unlockedCapabilities?: string[];
  prerequisiteChain?: string[];
  targetCompetency?: string;
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
}

export interface TodayOrchestrationPayload {
  registeredDomainEntries: number;
  expectedDomainEntries: number;
  continueSession?: OrchestrationNode<TodaySessionItem> | TodaySessionItem | null;
  nextAction?: OrchestrationNode<TodayNextActionItem> | TodayNextActionItem | null;
  rationale?: OrchestrationNode<TodayRationaleItem> | TodayRationaleItem | null;
  attentionItems?: OrchestrationNode<TodayAttentionItem[]> | TodayAttentionItem[] | null;
  recentContext?: OrchestrationNode<TodayRecentContextItem[]> | TodayRecentContextItem[] | null;
  progressProjection?: OrchestrationNode<TodayProgressProjection> | TodayProgressProjection | null;
}
