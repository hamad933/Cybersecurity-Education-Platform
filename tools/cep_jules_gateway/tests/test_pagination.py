from __future__ import annotations

import unittest

from tools.cep_jules_gateway.models import ErrorClassification, GatewayError
from tools.cep_jules_gateway.pagination import Page, paginate


class PaginationTests(unittest.TestCase):
    def test_one_page(self):
        result = paginate(lambda token: Page([{"id": 1}], None), max_pages=3)
        self.assertEqual([{"id": 1}], result.items)
        self.assertTrue(result.info.complete)
        self.assertEqual(1, result.info.pages_scanned)

    def test_multiple_pages(self):
        pages = {
            None: Page([{"id": 1}], "next"),
            "next": Page([{"id": 2}], None),
        }
        result = paginate(lambda token: pages[token], max_pages=3)
        self.assertEqual([1, 2], [x["id"] for x in result.items])
        self.assertEqual(2, result.info.pages_scanned)

    def test_limit_exceeded(self):
        def fetch(token):
            return Page([{"id": token or "first"}], f"t-{token or '1'}")

        with self.assertRaises(GatewayError) as ctx:
            paginate(fetch, max_pages=2)
        self.assertEqual(ctx.exception.classification, ErrorClassification.PAGINATION_LIMIT_EXCEEDED)

    def test_repeated_token_fails_closed(self):
        with self.assertRaises(GatewayError) as ctx:
            paginate(lambda token: Page([], "same"), max_pages=4)
        self.assertEqual(ctx.exception.classification, ErrorClassification.PROVIDER_READ_FAILED)


if __name__ == "__main__":
    unittest.main()
