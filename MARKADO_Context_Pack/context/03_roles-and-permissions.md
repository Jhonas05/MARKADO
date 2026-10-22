# Users, Roles, Authorization and Boundaries

## Source-supported roles [REQ]
**Student:** register/sign in, access eligible quizzes, read rules, attempt, submit and see results only when released. **Instructor/professor:** own and manage quizzes, materials, settings, review generated drafts, publish and inspect authorized results. **Academic administrator:** authorized account/assessment oversight; exact power set needs approval.

## Proposed authorization matrix [REC]
| Action | Student | Instructor | Academic admin |
|---|---|---|---|
| Register/login | Own account | Own account | Own account |
| See quiz instructions | Eligible quiz only | Owned/assigned | By approved scope |
| Create/edit quiz | No | Owned | If explicitly allowed |
| Publish/close quiz | No | Owned | If explicitly allowed |
| Manage sources and AI drafts | No | Owned | If explicitly allowed |
| Start/answer attempt | Eligible own attempt | No, except preview | No, except controlled QA |
| See scores | Own released score | In scope | Only if authorized |
| Export grades | No | In scope | Only with approved purpose |
| Regrade or override score | No | If explicitly authorized | Requires separate policy |
| View audit events | Own activity subset, if offered | Relevant owned activity | Administrative oversight by scope |

## Scope rules [REC]
Authorization must depend on **actor + action + resource scope**, not role string alone. For example, an instructor may view their Quiz A results but not another faculty member's Quiz B results. Student profile endpoints may return only their own data. Exports are particularly sensitive: test them independently from the UI.

## Avoid privilege ambiguities [OPEN]
- Who creates instructor/admin accounts, and is role verification required?
- Can administrators impersonate users for support? Recommended default: **no direct impersonation** without a separate controlled policy.
- Can a teaching assistant co-own/edit a quiz? How is removal handled?
- Can students enroll in several classes? What institution/course/section identity exists?
- Is the product for one school, several schools, or standalone quizzes?

## Access-control acceptance checks [REC]
- A different instructor changing an object ID cannot see, edit or export another instructor's quiz.
- A student cannot retrieve another student's attempt, answer key, grade or personal profile.
- Role changes take effect consistently on active sessions per approved policy.
- The server authorizes every change independently of whether a button is hidden in the UI.
- Administrative activity such as regrades and overrides records a reason and audit event.
