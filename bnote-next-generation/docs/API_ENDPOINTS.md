# BNote API Endpoints Reference
**Version:** 1.0  
**Date:** 2026-01-25  
**Base URL:** `/api/v1/`

---

## Table of Contents

1. [Authentication](#authentication)
2. [Dashboard](#dashboard)
3. [Rehearsals](#rehearsals)
4. [Concerts](#concerts)
5. [Contacts](#contacts)
6. [Calendar](#calendar)
7. [Tasks](#tasks)
8. [Messages](#messages)
9. [Programs](#programs)
10. [Members](#members)
11. [Finance](#finance)
12. [Equipment](#equipment)
13. [Repertoire](#repertoire)
14. [Locations](#locations)
15. [Groups](#groups)
16. [Instruments](#instruments)
17. [Votes/Polls](#votespolls)
18. [Comments (Discussion)](#comments-discussion)
19. [Appointments](#appointments)
19. [Tours](#tours)
20. [Travel](#travel)
21. [Accommodations](#accommodations)
22. [Outfits](#outfits)
23. [File Sharing](#file-sharing)
24. [Statistics](#statistics)
25. [Admin](#admin)
26. [Configuration](#configuration)
27. [Other Modules](#other-modules)

---

## Authentication

### POST /api/v1/auth/login

Authenticate user and create session.

**Request:**
```json
{
    "username": "user@example.com",
    "password": "password123"
}
```

**Response (200):**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 5,
            "name": "John",
            "surname": "Doe",
            "email": "user@example.com"
        },
        "permissions": [1, 3, 5, 7],
        "token": "..." // Optional, for mobile
    }
}
```

**Errors:**
- `401` - Invalid credentials
- `422` - Validation error

---

### POST /api/v1/auth/logout

Logout current user.

**Response (200):**
```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

---

### GET /api/v1/auth/session

Check current session status.

**Response (200):**
```json
{
    "success": true,
    "data": {
        "authenticated": true,
        "user": {
            "id": 5,
            "name": "John",
            "surname": "Doe"
        }
    }
}
```

**Response (401):**
```json
{
    "success": false,
    "error": {
        "code": "AUTH_REQUIRED",
        "message": "Authentication required"
    }
}
```

---

## Dashboard

### GET /api/v1/dashboard

Get dashboard data (inbox items, news, etc.).

**Response (200):**
```json
{
    "success": true,
    "data": {
        "news": "Welcome message...",
        "inbox": [
            {
                "otype": "R",
                "oid": 42,
                "title": "Probe am 29.01.2026 19:30",
                "dueDate": "2026-01-28",
                "participation": 1
            }
        ],
        "stats": {
            "upcoming_rehearsals": 5,
            "upcoming_concerts": 2,
            "open_tasks": 3
        }
    }
}
```

---

### GET /api/v1/dashboard/inbox

Get inbox items (rehearsals, concerts, tasks, votes, appointments).

**Query Parameters:**
- `otype` - Filter by type (R=rehearsal, C=concert, T=task, V=vote, A=appointment)
- `only` - Filter by specific type

**Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "otype": "R",
            "oid": 42,
            "title": "Probe am 29.01.2026 19:30",
            "preview": "Regular rehearsal...",
            "dueDate": "2026-01-28",
            "participation": 1
        }
    ]
}
```

---

## Rehearsals

### GET /api/v1/rehearsals

List all rehearsals.

**Query Parameters:**
- `page` - Page number (default: 1)
- `limit` - Items per page (default: 50)
- `sort` - Sort field (default: begin)
- `order` - Sort order (asc/desc, default: asc)
- `filter[status]` - Filter by status
- `filter[group]` - Filter by group ID
- `from` - Filter from date (YYYY-MM-DD)
- `to` - Filter to date (YYYY-MM-DD)

**Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 42,
            "begin": "2026-02-01 19:00:00",
            "end": "2026-02-01 21:00:00",
            "approve_until": "2026-01-31 23:59:59",
            "location": {
                "id": 5,
                "name": "Haus der Vereine",
                "address": {
                    "street": "Main St 1",
                    "city": "Berlin",
                    "zip": "10115"
                }
            },
            "conductor": {
                "id": 10,
                "name": "Franz",
                "surname": "Schledorn"
            },
            "status": "confirmed",
            "notes": "Regular rehearsal",
            "groups": [
                { "id": 2, "name": "Orchestra" }
            ]
        }
    ],
    "meta": {
        "pagination": {
            "page": 1,
            "limit": 50,
            "total": 150,
            "pages": 3
        }
    }
}
```

---

### GET /api/v1/rehearsals/{id}

Get single rehearsal with full details.

**Response (200):**
```json
{
    "success": true,
    "data": {
        "id": 42,
        "begin": "2026-02-01 19:00:00",
        "end": "2026-02-01 21:00:00",
        "location": { ... },
        "conductor": { ... },
        "groups": [ ... ],
        "songs": [
            {
                "id": 15,
                "title": "Symphony No. 5",
                "composer": "Beethoven",
                "rank": 1
            }
        ],
        "participants": [
            {
                "id": 5,
                "name": "John",
                "surname": "Doe",
                "instrument": "Violin",
                "participate": 1,
                "reason": ""
            }
        ],
        "stats": {
            "yes": 15,
            "no": 2,
            "maybe": 1,
            "total": 18
        }
    }
}
```

---

### POST /api/v1/rehearsals

Create new rehearsal.

**Request:**
```json
{
    "begin": "2026-02-01 19:00:00",
    "end": "2026-02-01 21:00:00",
    "approve_until": "2026-01-31 23:59:59",
    "location": 5,
    "conductor": 10,
    "serie": 3,
    "status": "confirmed",
    "notes": "Regular rehearsal",
    "groups": [2, 3]
}
```

**Response (201):**
```json
{
    "success": true,
    "data": {
        "id": 43,
        "begin": "2026-02-01 19:00:00",
        ...
    }
}
```

---

### PUT /api/v1/rehearsals/{id}

Update rehearsal.

**Request:** (Same as POST, full resource)

**Response (200):**
```json
{
    "success": true,
    "data": {
        "id": 42,
        ...
    }
}
```

---

### DELETE /api/v1/rehearsals/{id}

Delete rehearsal.

**Response (200):**
```json
{
    "success": true,
    "message": "Rehearsal deleted"
}
```

---

### GET /api/v1/rehearsals/{id}/participants

Get rehearsal participants.

**Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 5,
            "name": "John",
            "surname": "Doe",
            "nickname": "Johnny",
            "instrument": "Violin",
            "participate": 1,
            "reason": "",
            "replyon": "2026-01-25 10:00:00"
        }
    ]
}
```

---

### POST /api/v1/rehearsals/{id}/participate

Save user participation.

**Request:**
```json
{
    "participate": 1,  // 1=yes, 0=no, 2=maybe
    "reason": "Will attend"
}
```

**Response (200):**
```json
{
    "success": true,
    "data": {
        "participate": 1,
        "reason": "Will attend"
    }
}
```

---

### GET /api/v1/rehearsals/{id}/songs

Get songs for rehearsal.

**Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 15,
            "title": "Symphony No. 5",
            "composer": "Beethoven",
            "rank": 1,
            "notes": ""
        }
    ]
}
```

---

### POST /api/v1/rehearsals/{id}/songs

Add song to rehearsal.

**Request:**
```json
{
    "song": 15,
    "rank": 1,
    "notes": ""
}
```

**Response (201):**
```json
{
    "success": true,
    "data": {
        "id": 123,
        "song": 15,
        "rank": 1
    }
}
```

---

## Concerts

### GET /api/v1/concerts

List all concerts.

**Query Parameters:** (Same as rehearsals)

**Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 20,
            "title": "Spring Concert",
            "begin": "2026-03-15 19:00:00",
            "end": "2026-03-15 21:00:00",
            "meetingtime": "2026-03-15 18:00:00",
            "approve_until": "2026-03-10 23:59:59",
            "location": { ... },
            "program": {
                "id": 5,
                "name": "Spring Program"
            },
            "organizer": "Concert Hall",
            "status": "confirmed",
            "payment": 500.00,
            "notes": "..."
        }
    ]
}
```

---

### GET /api/v1/concerts/{id}

Get single concert.

**Response (200):**
```json
{
    "success": true,
    "data": {
        "id": 20,
        "title": "Spring Concert",
        "begin": "2026-03-15 19:00:00",
        "end": "2026-03-15 21:00:00",
        "location": { ... },
        "program": { ... },
        "participants": [ ... ],
        "contact": { ... },
        "accommodation": { ... },
        "outfit": { ... }
    }
}
```

---

### POST /api/v1/concerts

Create new concert.

**Request:**
```json
{
    "title": "Spring Concert",
    "begin": "2026-03-15 19:00:00",
    "end": "2026-03-15 21:00:00",
    "meetingtime": "2026-03-15 18:00:00",
    "approve_until": "2026-03-10 23:59:59",
    "location": 5,
    "program": 5,
    "contact": 10,
    "organizer": "Concert Hall",
    "status": "confirmed",
    "payment": 500.00,
    "notes": "..."
}
```

---

### PUT /api/v1/concerts/{id}

Update concert.

---

### DELETE /api/v1/concerts/{id}

Delete concert.

---

### POST /api/v1/concerts/{id}/participate

Save user participation (same as rehearsals).

---

## Contacts

### GET /api/v1/contacts

List all contacts.

**Query Parameters:**
- `group` - Filter by group ID
- `instrument` - Filter by instrument ID
- `search` - Search by name/email

**Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 10,
            "name": "Franz",
            "surname": "Schledorn",
            "nickname": "Franzi",
            "email": "franz@example.com",
            "phone": "+49 123 456789",
            "mobile": "+49 987 654321",
            "instrument": {
                "id": 3,
                "name": "Conductor"
            },
            "address": {
                "street": "Main St 1",
                "city": "Berlin",
                "zip": "10115"
            },
            "groups": [
                { "id": 1, "name": "Administrators" },
                { "id": 2, "name": "Orchestra" }
            ],
            "is_conductor": true,
            "birthday": "1970-01-01",
            "status": "active"
        }
    ]
}
```

---

### GET /api/v1/contacts/{id}

Get single contact.

---

### POST /api/v1/contacts

Create new contact.

**Request:**
```json
{
    "name": "John",
    "surname": "Doe",
    "nickname": "Johnny",
    "email": "john@example.com",
    "phone": "+49 123 456789",
    "instrument": 3,
    "address": 5,
    "groups": [2],
    "is_conductor": false,
    "birthday": "1990-01-01",
    "status": "active"
}
```

---

### PUT /api/v1/contacts/{id}

Update contact.

---

### DELETE /api/v1/contacts/{id}

Delete contact.

---

### GET /api/v1/contacts/{id}/groups

Get contact's groups.

---

### POST /api/v1/contacts/{id}/groups

Add contact to group.

**Request:**
```json
{
    "group": 2
}
```

---

### DELETE /api/v1/contacts/{id}/groups/{groupId}

Remove contact from group.

---

## Calendar

### GET /api/v1/calendar/events

Get calendar events (rehearsals, concerts, appointments).

**Query Parameters:**
- `from` - Start date (YYYY-MM-DD, required)
- `to` - End date (YYYY-MM-DD, required)
- `type` - Filter by type (rehearsal, concert, appointment)

**Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 42,
            "type": "rehearsal",
            "title": "Probe am 29.01.2026 19:30",
            "begin": "2026-01-29 19:00:00",
            "end": "2026-01-29 21:00:00",
            "location": "Haus der Vereine",
            "color": "#3b82f6"
        },
        {
            "id": 20,
            "type": "concert",
            "title": "Spring Concert",
            "begin": "2026-03-15 19:00:00",
            "end": "2026-03-15 21:00:00",
            "location": "Concert Hall",
            "color": "#10b981"
        }
    ]
}
```

---

## Tasks

### GET /api/v1/tasks

List tasks.

**Query Parameters:**
- `assigned_to` - Filter by assignee ID
- `created_by` - Filter by creator ID
- `is_complete` - Filter by completion (true/false)
- `due_from` - Filter from due date
- `due_to` - Filter to due date

**Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 15,
            "title": "Prepare program",
            "description": "Create program for spring concert",
            "created_at": "2026-01-20 10:00:00",
            "created_by": {
                "id": 5,
                "name": "John",
                "surname": "Doe"
            },
            "due_at": "2026-02-01 23:59:59",
            "assigned_to": {
                "id": 10,
                "name": "Franz",
                "surname": "Schledorn"
            },
            "is_complete": false,
            "completed_at": null
        }
    ]
}
```

---

### GET /api/v1/tasks/{id}

Get single task.

---

### POST /api/v1/tasks

Create new task.

**Request:**
```json
{
    "title": "Prepare program",
    "description": "Create program for spring concert",
    "due_at": "2026-02-01 23:59:59",
    "assigned_to": 10
}
```

---

### PUT /api/v1/tasks/{id}

Update task.

---

### DELETE /api/v1/tasks/{id}

Delete task.

---

### POST /api/v1/tasks/{id}/complete

Mark task as complete.

**Request:**
```json
{
    "is_complete": true
}
```

**Response (200):**
```json
{
    "success": true,
    "data": {
        "id": 15,
        "is_complete": true,
        "completed_at": "2026-01-25 12:00:00"
    }
}
```

---

## Messages

### GET /api/v1/messages

List messages/news.

**Query Parameters:**
- `type` - Filter by type (message, news, discussion)
- `related_type` - Filter by related object type (R, C, etc.)
- `related_id` - Filter by related object ID

**Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 8,
            "title": "Important Announcement",
            "message": "Please note...",
            "author": {
                "id": 5,
                "name": "John",
                "surname": "Doe"
            },
            "created_at": "2026-01-20 10:00:00",
            "related_type": "R",
            "related_id": 42
        }
    ]
}
```

---

### GET /api/v1/messages/{id}

Get single message.

---

### POST /api/v1/messages

Create new message.

**Request:**
```json
{
    "title": "Important Announcement",
    "message": "Please note...",
    "related_type": "R",
    "related_id": 42
}
```

---

### POST /api/v1/messages/{id}/comments

Add comment to message/discussion.

**Request:**
```json
{
    "message": "I agree with this"
}
```

---

## Programs

### GET /api/v1/programs

List all programs/setlists.

**Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 5,
            "name": "Spring Program",
            "notes": "Program for spring concert",
            "is_template": false,
            "songs": [
                {
                    "id": 15,
                    "rank": 1,
                    "title": "Symphony No. 5",
                    "composer": "Beethoven",
                    "length": "30:00",
                    "notes": ""
                }
            ],
            "total_length": "90:00"
        }
    ]
}
```

---

### GET /api/v1/programs/{id}

Get single program with songs.

---

### POST /api/v1/programs

Create new program.

**Request:**
```json
{
    "name": "Spring Program",
    "notes": "Program for spring concert",
    "is_template": false
}
```

---

### PUT /api/v1/programs/{id}

Update program.

---

### DELETE /api/v1/programs/{id}

Delete program.

---

### GET /api/v1/programs/{id}/songs

Get program songs.

---

### POST /api/v1/programs/{id}/songs

Add song to program.

**Request:**
```json
{
    "song": 15,
    "rank": 1,
    "notes": ""
}
```

---

### DELETE /api/v1/programs/{id}/songs/{songId}

Remove song from program.

---

### POST /api/v1/programs/{id}/reorder

Reorder program songs.

**Request:**
```json
{
    "songs": [
        { "id": 15, "rank": 1 },
        { "id": 20, "rank": 2 },
        { "id": 18, "rank": 3 }
    ]
}
```

---

## Members

### GET /api/v1/members

List members (contacts in member group).

**Query Parameters:** (Same as contacts)

---

## Finance

### GET /api/v1/finance/receipts

List receipts.

**Query Parameters:**
- `from` - Filter from date
- `to` - Filter to date
- `contact` - Filter by contact ID

---

### GET /api/v1/finance/payments

List payments.

---

### POST /api/v1/finance/receipts

Create receipt.

---

### POST /api/v1/finance/payments

Create payment.

---

## Equipment

### GET /api/v1/equipment

List equipment items.

---

### POST /api/v1/equipment

Create equipment item.

---

## Repertoire

### GET /api/v1/repertoire/songs

List songs.

**Query Parameters:**
- `search` - Search by title/composer
- `genre` - Filter by genre ID

---

### GET /api/v1/repertoire/songs/{id}

Get single song.

---

### POST /api/v1/repertoire/songs

Create song.

---

## Locations

### GET /api/v1/locations

List locations.

---

### GET /api/v1/locations/{id}

Get location with address.

---

### POST /api/v1/locations

Create location.

---

## Groups

### GET /api/v1/groups

List groups.

**Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Administrators",
            "is_active": true,
            "members": [
                { "id": 5, "name": "John", "surname": "Doe" }
            ]
        },
        {
            "id": 2,
            "name": "Orchestra",
            "is_active": true,
            "members": [ ... ]
        }
    ]
}
```

---

### GET /api/v1/groups/{id}

Get group with members.

---

### POST /api/v1/groups

Create group.

---

## Instruments

### GET /api/v1/instruments

List instruments.

**Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Violin",
            "rank": 1
        },
        {
            "id": 2,
            "name": "Viola",
            "rank": 2
        }
    ]
}
```

**Note:** This is static data, should be cached.

---

## Votes/Polls

### GET /api/v1/votes

List votes/polls.

---

### GET /api/v1/votes/{id}

Get vote with options and results.

---

### POST /api/v1/votes/{id}/vote

Submit vote.

**Request:**
```json
{
    "option": 5,  // Option ID
    "choice": "yes"  // yes, no, maybe
}
```

---

## Comments (Discussion)

Comments (chat) for rehearsals, concerts, and votes. Requires `discussion_on` config to be enabled; otherwise list/add return 403.

**Actual URL pattern:** `GET|POST .../api/index.php?module=comments&action=...`

### GET comments list

`?module=comments&action=list&otype=R|C|V&oid={id}`

- `otype`: `R` (rehearsal), `C` (concert), or `V` (vote)
- `oid`: Entity ID

**Response (200):**
```json
[
  {
    "id": 1,
    "author": "John Doe",
    "author_id": 5,
    "message": "See you there!",
    "created_at": "2026-02-25 14:30:00"
  }
]
```

Order: oldest first. Returns 403 if discussion is disabled or user has no access.

---

### POST add comment

`POST ?module=comments&action=add` with body:

```json
{
  "otype": "R",
  "oid": 123,
  "message": "My comment text"
}
```

**Response (200):** The new comment object (same shape as list items). Sends email notification to contacts with notification enabled (skipped in DemoMode). Returns 403 if discussion disabled or no access; 400 if message empty or invalid.

---

### POST delete comment

`POST ?module=comments&action=delete` with body `{ "id": 123 }` or query `id=123`.

Only the comment author can delete. Returns 404 if comment not found, 403 if not author.

**Response (200):**
```json
{ "deleted": true }
```

---

## Appointments

### GET /api/v1/appointments

List appointments.

---

### POST /api/v1/appointments

Create appointment.

---

## Tours

### GET /api/v1/tours

List tours.

---

## Travel

### GET /api/v1/travel

List travel entries.

---

## Accommodations

### GET /api/v1/accommodations

List accommodations.

---

## Outfits

### GET /api/v1/outfits

List outfits.

---

## File Sharing

### GET /api/v1/share/files

List files.

**Query Parameters:**
- `path` - Directory path
- `type` - Filter by type (file/folder)

---

### POST /api/v1/share/files

Upload file.

**Request:** (multipart/form-data)
- `file` - File data
- `path` - Target directory
- `name` - File name

---

### GET /api/v1/share/files/{path}

Download file.

**Response:** File content with appropriate Content-Type

---

### DELETE /api/v1/share/files/{path}

Delete file.

---

## Statistics

### GET /api/v1/stats/overview

Get statistics overview.

**Response (200):**
```json
{
    "success": true,
    "data": {
        "total_members": 50,
        "total_rehearsals": 150,
        "total_concerts": 20,
        "attendance_rate": 85.5
    }
}
```

---

## Admin

### GET /api/v1/admin/users

List users.

**Note:** Admin only

---

### POST /api/v1/admin/users

Create user.

---

### GET /api/v1/admin/modules

List modules with permissions.

---

### POST /api/v1/admin/permissions

Grant permission.

**Request:**
```json
{
    "user": 5,
    "module": 3
}
```

---

## Configuration

### GET /api/v1/config

Get configuration.

**Response (200):**
```json
{
    "success": true,
    "data": {
        "company_name": "My Orchestra",
        "currency": "EUR",
        "theme": "default",
        "user_registration": true
    }
}
```

---

### PUT /api/v1/config

Update configuration.

**Note:** Admin only

---

## Other Modules

### Rehearsal Phases
- `GET /api/v1/rehearsal-phases`
- `POST /api/v1/rehearsal-phases`
- etc.

### Custom Fields
- `GET /api/v1/custom-fields?type=c` (c=contact, r=rehearsal, etc.)
- `POST /api/v1/custom-fields`
- etc.

### Genres
- `GET /api/v1/genres`

### Website
- `GET /api/v1/website/pages`
- `POST /api/v1/website/pages`
- etc.

---

## Common Patterns

### Pagination

All list endpoints support pagination:
```
GET /api/v1/{resource}?page=1&limit=50
```

### Filtering

Filter by field:
```
GET /api/v1/{resource}?filter[field]=value
```

### Sorting

Sort by field:
```
GET /api/v1/{resource}?sort=field&order=asc
```

### Field Selection

Select specific fields:
```
GET /api/v1/{resource}?fields=id,name,email
```

### Search

Full-text search:
```
GET /api/v1/{resource}?search=query
```

---

## Error Responses

All endpoints return errors in this format:

```json
{
    "success": false,
    "error": {
        "code": "ERROR_CODE",
        "message": "Human-readable message",
        "details": [
            {
                "field": "begin",
                "message": "Begin date is required"
            }
        ]
    }
}
```

**Common Error Codes:**
- `AUTH_REQUIRED` (401)
- `AUTH_FORBIDDEN` (403)
- `RESOURCE_NOT_FOUND` (404)
- `VALIDATION_ERROR` (422)
- `INTERNAL_ERROR` (500)

---

**Document Status:** Complete  
**Last Updated:** 2026-01-25  
**Next:** See `JS_ARCHITECTURE.md` for JavaScript frontend architecture
