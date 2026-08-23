"""
CEP Fast Control Plane - Task Budget Ledger & Quota Policy.
Standard library only (Python 3.10+).

Enforces conservative task usage:
- Hard project automation ceiling of 70 Jules tasks total.
- Reserve threshold of 15 tasks reserved below 70 (warning / block non-critical new tasks at >= 55).
- Support observed starting count from environment/config, or UNKNOWN if unproven.
- Prefer existing session continuation over new session creation.
"""

from dataclasses import dataclass
from typing import Optional


@dataclass
class BudgetStatus:
    hard_ceiling: int = 70
    reserve_margin: int = 15
    observed_used_count: Optional[int] = None
    warning_threshold: int = 55
    can_create_new_task: bool = False
    is_exhausted_or_unknown: bool = False
    reason: str = ""


class TaskBudgetLedger:
    def __init__(
        self,
        hard_ceiling: int = 70,
        reserve_margin: int = 15,
        observed_used_count: Optional[int] = None,
    ):
        self.hard_ceiling = hard_ceiling
        self.reserve_margin = reserve_margin
        self.observed_used_count = observed_used_count
        self.warning_threshold = self.hard_ceiling - self.reserve_margin  # 55

    def check_new_task_allowed(self, is_critical_assurance: bool = False) -> BudgetStatus:
        if self.observed_used_count is None:
            return BudgetStatus(
                hard_ceiling=self.hard_ceiling,
                reserve_margin=self.reserve_margin,
                observed_used_count=None,
                warning_threshold=self.warning_threshold,
                can_create_new_task=False,
                is_exhausted_or_unknown=True,
                reason="Task budget state is UNKNOWN / unproven. Block new session creation.",
            )

        used = self.observed_used_count

        if used >= self.hard_ceiling:
            return BudgetStatus(
                hard_ceiling=self.hard_ceiling,
                reserve_margin=self.reserve_margin,
                observed_used_count=used,
                warning_threshold=self.warning_threshold,
                can_create_new_task=False,
                is_exhausted_or_unknown=True,
                reason=f"Observed tasks ({used}) reached or exceeded hard ceiling ({self.hard_ceiling}).",
            )

        if used >= self.warning_threshold:
            if is_critical_assurance:
                return BudgetStatus(
                    hard_ceiling=self.hard_ceiling,
                    reserve_margin=self.reserve_margin,
                    observed_used_count=used,
                    warning_threshold=self.warning_threshold,
                    can_create_new_task=True,
                    is_exhausted_or_unknown=False,
                    reason=f"Observed tasks ({used}) in reserve region (>= {self.warning_threshold}), allowed for critical assurance.",
                )
            else:
                return BudgetStatus(
                    hard_ceiling=self.hard_ceiling,
                    reserve_margin=self.reserve_margin,
                    observed_used_count=used,
                    warning_threshold=self.warning_threshold,
                    can_create_new_task=False,
                    is_exhausted_or_unknown=False,
                    reason=f"Observed tasks ({used}) reached reserve margin (>= {self.warning_threshold}). Non-critical new task denied.",
                )

        return BudgetStatus(
            hard_ceiling=self.hard_ceiling,
            reserve_margin=self.reserve_margin,
            observed_used_count=used,
            warning_threshold=self.warning_threshold,
            can_create_new_task=True,
            is_exhausted_or_unknown=False,
            reason=f"Budget healthy ({used}/{self.hard_ceiling} used). New task permitted.",
        )
