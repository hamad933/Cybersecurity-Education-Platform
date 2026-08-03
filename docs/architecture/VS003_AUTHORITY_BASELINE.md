# VS-003 authority baseline

Baseline ID: `WINDOWS-AUTH-TELEMETRY-IR-2026-07-24-V1`
Access date: 2026-07-24

The simulator uses a narrow, synthetic Windows Audit Logon baseline: event 4624 is a successful logon and event 4625 is a failed logon. The implementation preserves event ID, occurrence time, computer, account/SID, logon type, workstation, source address, and explicit field availability; it does not claim that any absent field is present for all authentication contexts. Microsoft documents that network fields can vary by authentication context and protocol.

NIST SP 800-61r3 (April 2025) supports bounded incident-response recommendations in the CSF 2.0 context. NIST CSF 2.0 supports an authority-aware, assessment-led response and proportionate containment proposal. Neither source authorizes live containment, forensic/legal admissibility, production SIEM behavior, or a claim that synthetic telemetry represents a real Windows estate.

All VS-003 events, findings, decisions, custody, containment, control revisions, verification, and evidence are `SIMULATED`.
