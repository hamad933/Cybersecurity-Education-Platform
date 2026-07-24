# Request authorization results

PASS. Parameterized tests cover all twelve `CASE-WEB-*` cases and deterministic digests. Authentication, lookup and authorization are distinct. Client role/owner spoof fields are ignored. Wrong method/action, absent resource, malformed ID and unsupported policy fail without guessed allow. The serializer includes only `id`, `title`, `status`, and `owner_display`.
