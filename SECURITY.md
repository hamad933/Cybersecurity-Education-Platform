# Security policy

Report suspected vulnerabilities privately to the designated repository owner; do not place credentials or exploit details in an issue or review packet.

The Task 006 threat surface is local owner authentication plus platform primitives. There is no registration, password-reset email, API token, social login, automated AI, or real-execution connector. Authentication bypass is hard-disabled. Run `composer security` before review. Rotate any local secret that is accidentally disclosed and remove it from Git history before sharing artifacts.
