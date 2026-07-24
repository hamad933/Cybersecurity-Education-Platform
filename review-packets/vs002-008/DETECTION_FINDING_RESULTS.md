# Detection and finding results

PASS. A deterministic access-control finding is emitted only for proven vulnerable cross-owner allow; a serializer finding records excluded fields. The key is idempotent, details are bounded, and tests assert no password/Bearer content and `secrets_stored=false`. Evidence/outbox records are produced once per run.
