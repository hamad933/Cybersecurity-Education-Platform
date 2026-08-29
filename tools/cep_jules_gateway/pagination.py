from __future__ import annotations

from dataclasses import dataclass
from typing import Any, Callable

from .models import ErrorClassification, GatewayError, PaginationInfo


@dataclass(frozen=True)
class Page:
    items: list[dict[str, Any]]
    next_page_token: str | None


@dataclass(frozen=True)
class PaginationResult:
    items: list[dict[str, Any]]
    info: PaginationInfo


def paginate(
    fetch_page: Callable[[str | None], Page],
    *,
    max_pages: int,
    max_items: int | None = None,
) -> PaginationResult:
    if max_pages < 1:
        raise ValueError("max_pages must be >= 1")
    if max_items is not None and max_items < 1:
        raise ValueError("max_items must be >= 1")

    items: list[dict[str, Any]] = []
    token: str | None = None
    seen_tokens: set[str] = set()

    for page_number in range(1, max_pages + 1):
        page = fetch_page(token)
        if max_items is not None and len(items) + len(page.items) > max_items:
            raise GatewayError(
                ErrorClassification.READ_BUDGET_EXCEEDED,
                "provider item collection would exceed the configured total item bound",
                details={
                    "pages_scanned": page_number,
                    "items_scanned_before_page": len(items),
                    "page_items": len(page.items),
                    "limit_items": max_items,
                    "continuation_from_before_page": token,
                },
            )
        items.extend(page.items)
        next_token = (page.next_page_token or "").strip() or None
        if next_token is None:
            return PaginationResult(items, PaginationInfo(page_number, len(items), True, max_pages, max_items))
        if next_token in seen_tokens or next_token == token:
            raise GatewayError(
                ErrorClassification.PROVIDER_READ_FAILED,
                "provider pagination token repeated; refusing an infinite read loop",
                details={"pages_scanned": page_number, "items_scanned": len(items)},
            )
        if max_items is not None and len(items) == max_items:
            return PaginationResult(
                items,
                PaginationInfo(
                    page_number,
                    len(items),
                    False,
                    max_pages,
                    max_items,
                    next_page_token=next_token,
                    stop_reason="TOTAL_ITEM_BOUND_REACHED",
                ),
            )
        seen_tokens.add(next_token)
        token = next_token

    if max_items is None:
        # Preserve the Foundation v2.0 fail-closed contract for callers that
        # did not opt into explicit continuation metadata.
        raise GatewayError(
            ErrorClassification.PAGINATION_LIMIT_EXCEEDED,
            "provider pagination exceeded the configured safety bound",
            details={"pages_scanned": max_pages, "items_scanned": len(items)},
        )
    return PaginationResult(
        items,
        PaginationInfo(
            max_pages,
            len(items),
            False,
            max_pages,
            max_items,
            next_page_token=token,
            stop_reason="TOTAL_PAGE_BOUND_REACHED",
        ),
    )
