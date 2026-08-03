# VS-001 Windows authority baseline

Status: approved technical baseline for the bounded VS-001 rule set. Accessed and reviewed 2026-07-22.

## Selected target

`WIN11-24H2-26100-FILE-AUTHZ-V1` means Windows 11 version 24H2, OS-build family 26100, x64, local file securable objects, and an educational subset of explicit ordered DACL evaluation. Microsoft lists Windows 11 24H2 with OS build 26100; the target is a build family rather than a promise that every later cumulative build has identical behavior.

The implemented object mapping is `WINDOWS11_24H2_FILE_V1`. The implementation does not invoke Windows, `AccessCheck`, PowerShell, WinRM, a host process, a device, or any connector.

## Primary authority reviewed

| Claim | Primary document | Exact URL | Reviewed segment | Scope used |
|---|---|---|---|---|
| WIN-AUTH-002 | Windows 11 release information | https://learn.microsoft.com/en-us/windows/release-health/windows11-release-information | Current versions, Version 24H2, OS build 26100 | Target release/build family only |
| WIN-AUTH-003 | AccessCheck function | https://learn.microsoft.com/en-us/windows/win32/api/securitybaseapi/nf-securitybaseapi-accesscheck | Parameters and Remarks | Descriptor/token preconditions and NULL-DACL behavior |
| WIN-AUTH-004 | DACLs and ACEs | https://learn.microsoft.com/en-us/windows/win32/secauthz/dacls-and-aces | ACE order, access-denied and access-allowed behavior | Ordered explicit DACL subset and cumulative allows |
| WIN-AUTH-005 | SID attributes in an access token | https://learn.microsoft.com/en-us/windows/win32/secauthz/sid-attributes-in-an-access-token | `SE_GROUP_ENABLED`, `SE_GROUP_USE_FOR_DENY_ONLY` | Enabled and deny-only group matching |
| WIN-AUTH-006 | MapGenericMask function | https://learn.microsoft.com/en-us/windows/win32/api/securitybaseapi/nf-securitybaseapi-mapgenericmask | Generic mapping contract | Mapping only when the approved FILE mapping is declared |
| WIN-AUTH-007 | File security and access rights | https://learn.microsoft.com/en-us/windows/win32/fileio/file-security-and-access-rights | Generic access-right mapping for files | File generic read/write/execute/all masks |

Microsoft Open Specifications were also reviewed for structure and terminology: [MS-DTYP 2.4.5 ACL](https://learn.microsoft.com/en-us/openspecs/windows_protocols/ms-dtyp/20233ed8-a6c6-4097-aafa-dd545ed24428) and [MS-DTYP 2.4.4 ACE_HEADER](https://learn.microsoft.com/en-us/openspecs/windows_protocols/ms-dtyp/628ebb1d-c509-4ea0-a10f-77ef97ca4586). They support the bounded representation of ordered ACL entries but are not used to claim full protocol or kernel fidelity.

## Supported semantics

- Valid user SID plus ordered enabled/deny-only group SID attributes.
- Declared local `FILE` object, owner, DACL, requested mask, explicit access-denied/access-allowed ACE types, trustee, mask, and minimal applicability flags.
- NULL DACL allow; empty or incomplete grants leave a remaining mask and produce deny in this approved subset.
- Decisive explicit deny before later allow; applicable allows accumulate until the remaining requested mask is zero.
- File generic-right mapping only under `WINDOWS11_24H2_FILE_V1`.
- Deterministic `ALLOW`, `DENY`, `INSUFFICIENT_STATE`, or `UNSUPPORTED_STATE`, never a guessed result.

## Explicit exclusions

No inheritance expansion, object-specific or conditional ACEs, integrity labels, restricted tokens, claims, central policies, privilege bypass semantics, owner implicit rights, share permissions, remote behavior, auditing, kernel parity, or real-lab validation. Declared privileges return `UNSUPPORTED_STATE`. Missing principal, descriptor, mapping, or malformed bounded input returns `INSUFFICIENT_STATE`. Unknown ACE or object types return `UNSUPPORTED_STATE`.

The internal reviewed source selection remains `Internal Reviewed Support`, not technical authority. Its relative path and digest are preserved in `source_records`; it cannot override these Microsoft sources.
