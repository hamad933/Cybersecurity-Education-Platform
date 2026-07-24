# VS-002 module interaction

`App\Application\Vs002` coordinates module-owned application services without importing ORM models. MOD-SRC owns reviewed claims. MOD-KNO owns lesson revisions and publication. MOD-ENT exposes the published baseline snapshot. MOD-SIM owns request plans, policies, contracts, runs, traces and replay. MOD-EVD owns findings, verifications, evidence and review decisions. MOD-LRN owns practice attempts, mastery and failure-based review.

The Simulator does not import Evidence or Learning implementations. Controllers call coordinators and publication services only. Database writes remain in the owning module. The locked Task-006 dependency graph remains the authority and no MOD-AIB implementation exists.
